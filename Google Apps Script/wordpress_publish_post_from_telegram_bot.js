// Definizione delle variabili globali
var gTokenBot = ''; // Valorizza con il tuo token del bot di Telegram
var gUrlDeployGoogleAppsScript = ''; // Sostituisci con l'URL del tuo webhook
var gUrlBlog = ''; //Sostituisci con la root del blog
var gUsernameBlog = ''; // Sostituisci con il tuo username di autenticazione su Wordpress
var gPasswordBlog = ''; // Sostituisci con la tua password o password dell'app

var gAdminChatId = ''; //Valorizza con la tua chatid di Telegram


function doPost(e) {
  try {
    if (e.postData) {
      var update = JSON.parse(e.postData.contents);
      // Make sure this is update is a type message
      if (update.hasOwnProperty('message')) {
        var msg = update.message;
        var chatId = msg.chat.id;
        
        // Mi assicuro che si tratti di un comando
        if (msg.hasOwnProperty('entities') && msg.entities[0].type == 'bot_command') {
          // Inizio elenco comandi
          if (msg.text == '/start') {
            sendMessageToTelegram(gTokenBot, 'Bot avviato correttamente', chatId);
          } else if (msg.text == '/whois') {
            sendMessageToTelegram(gTokenBot, 'Il tuo chatid è ' + chatId, chatId);
          } else if (msg.text == '/version') {
            sendMessageToTelegram(gTokenBot, 'Versione 1 stabile' , chatId);
          }
          //Fine elenco comandi

        } else {
          
          //Se è una foto
          if (chatId == gAdminChatId && msg.photo) {
            sendMessageToTelegram(gTokenBot, 'Rilevato comando invio immagine', chatId);
            // Telegram invia le foto come array di oggetti
            var photos = msg.photo;

            // Prendi solo il primo oggetto foto
            var firstPhoto = photos[photos.length - 1]; // L'oggetto con la dimensione più alta è l'ultimo
            // Ottieni l'ID del file della foto
            var fileId = firstPhoto.file_id;
            // Richiesta per ottenere il blob dell'immagine
            var url = 'https://api.telegram.org/bot' + gTokenBot + '/getFile?file_id=' + fileId;
            var response = UrlFetchApp.fetch(url);
            var fileData = JSON.parse(response.getContentText());

            // Ottieni il percorso del file
            var filePath = fileData.result.file_path;

            // Ottieni il blob dell'immagine
            var imageUrl = 'https://api.telegram.org/file/bot' + gTokenBot + '/' + filePath;
            
            if (msg.caption && msg.caption.length>0) {
              var titleText = separateString(msg.caption)[0];
              var contentText = separateString(msg.caption)[1];
            } else {
              var titleText = 'Foto rapida';
              var contentText = '';
            }
            
            var postId = publishPostOnWordpressWithImage(gUrlBlog, titleText, contentText, imageUrl);

            if (postId) {
              sendMessageToTelegram(gTokenBot, 'Post pubblicato correttamente', chatId);
              sendMessageToTelegram(gTokenBot, gUrlBlog+'/?p='+postId, chatId);
            } else {
              sendMessageToTelegram(gTokenBot, 'Errore pubblicazione del post', chatId);
            }
          } //Se è un semplice messaggio di testo
            else if (chatId == gAdminChatId && msg.text && msg.text.length > 0) {
            sendMessageToTelegram(gTokenBot, 'Rilevato invio testo', chatId);

            var titleText = separateString(msg.text)[0];
            var contentText = separateString(msg.text)[1];
            var postId = publishPostOnWordpress(gUrlBlog, titleText, contentText);

            if (postId) {
              sendMessageToTelegram(gTokenBot, 'Post pubblicato correttamente', chatId);
              sendMessageToTelegram(gTokenBot, gUrlBlog+'/?p='+postId, chatId);
            } else {
              sendMessageToTelegram(gTokenBot, 'Errore pubblicazione del post', chatId);
            }
            
            
          } //SE non è niente l'utente non è autorizzato lo avvisa e comunica l'accesso all'admin
            else {
            sendMessageToTelegram(gTokenBot, 'Non sei abilitato ad utilizzare questo bot, spiacente', chatId);
            sendMessageToTelegram(gTokenBot, 'Un utente esterno con ID '+chatId+' sta tentando di utilizzare il bot senza autorizzazione', gAdminChatId);
          }
        }
      }
    } else {
      Logger.log('Ricevuto un PostData non corretto');
    }



  } catch (e) {
    Logger.log('Errore: ' + e.message);
  }
}

function sendMessageToTelegram(token, text, chatId) {

  var payload = {
    'method': 'sendMessage',
    'chat_id': String(chatId),
    'text': text,
    'parse_mode': 'HTML'
  }

  var data = {
    "method": "post",
    "payload": payload
  }

  UrlFetchApp.fetch('https://api.telegram.org/bot' + token + '/', data);
}

function publishPostOnWordpress(domain, titleText, contentText) {
  var url = domain + '/wp-json/wp/v2/posts'; // Sostituisci con l'URL del tuo sito
  
  var postData = {
    title: titleText,
    content: contentText,
    status: 'publish',
    categories: [1] // Puoi usare 'draft' se vuoi salvare come bozza
  };

  var options = {
    method: 'post',
    contentType: 'application/json',
    payload: JSON.stringify(postData),
    headers: {
      'Authorization': 'Basic ' + Utilities.base64Encode(gUsernameBlog + ':' + gPasswordBlog)
    }
  };

  var response = UrlFetchApp.fetch(url, options);
  var jsonResponse = JSON.parse(response.getContentText()); // Analizza la risposta JSON
  var postId = jsonResponse.id;
  postId = parseInt(postId);
  Logger.log(response.getContentText());
  return postId;
}

function publishPostOnWordpressWithImage(domain, titleText, contentText, imageUrl) {
  
  var mediaUrl = domain + '/wp-json/wp/v2/media'; // Endpoint per caricare media
  var imageBlob = UrlFetchApp.fetch(imageUrl).getBlob();
  var imageName = imageUrl.split('/').pop();
  var mimeType = getMimeTypeFromFileName(imageName);

  var mediaOptions = {
    method: 'post',
    contentType: mimeType,
    payload: imageBlob,
    headers: {
      'Authorization': 'Basic ' + Utilities.base64Encode(gUsernameBlog + ':' + gPasswordBlog),
      'Content-Disposition': 'attachment; filename="' + imageName + '"'
    }
  };

  try {
    // Carica l'immagine
    var mediaResponse = UrlFetchApp.fetch(mediaUrl, mediaOptions);
    var mediaData = JSON.parse(mediaResponse.getContentText());
    var postImageUrl = mediaData.source_url; // Ottieni l'URL dell'immagine caricata
    var postImageId = mediaData.id; // Ottieni l'ID dell'immagine caricata
    
    // Crea il contenuto del post con l'immagine
    var postContent = '<!-- wp:image {"id":' + postImageId + ',"sizeSlug":"full","linkDestination":"none","align":"center"} -->' +
      '<figure class="wp-block-image aligncenter size-full"><img src="' + postImageUrl + '" alt="" class="wp-image-' + postImageId + '"/></figure>' +
      '<!-- /wp:image -->' +
      '<p>' + sanitizeInput(contentText) + '</p>';
    // Ora pubblica il post
    var postId = publishPostOnWordpress(domain, titleText, postContent);

    return postId;
  } catch (error) {
    Logger.log('Errore: ' + error.message);
    return null;
  }
}

function sanitizeInput(input) {
  // Rimuovi caratteri speciali e sostituisci con uno spazio
  return input
    .replace(/&/g, '&amp;') // Sostituisci & con &amp;
    .replace(/</g, '&lt;')  // Sostituisci < con &lt;
    .replace(/>/g, '&gt;')  // Sostituisci > con &gt;
    .replace(/"/g, '&quot;') // Sostituisci " con &quot;
    .replace(/'/g, '&#39;')  // Sostituisci ' con &#39;
    .replace(/`/g, '&#96;')  // Sostituisci ` con &#96;
    .replace(/\\/g, '\\\\'); // Raddoppia le backslash
}

function separateString(inputString) {

  var firstDotIndex = inputString.indexOf('.');
    // Trova la posizione del primo accapo
    var firstNewlineIndex = inputString.indexOf('\n');

    // Determina la posizione minima tra il primo punto e il primo accapo
    var separatorIndex = -1;

    if (firstDotIndex !== -1 && (firstNewlineIndex === -1 || firstDotIndex < firstNewlineIndex)) {
      separatorIndex = firstDotIndex;
    } else if (firstNewlineIndex !== -1) {
      separatorIndex = firstNewlineIndex;
    }

    // Se non ci sono punti o accapo, tutta la stringa va nella prima parte
    if (separatorIndex === -1) {
      return ['Nota veloce', inputString];
    }

    // Separa la stringa in due parti
    var firstPart = inputString.substring(0, separatorIndex).trim(); // Include il punto o l'accapo
    var secondPart = inputString.substring(separatorIndex + 1).trim(); // Rimuove eventuali spazi all'inizio


  return [firstPart, secondPart];
}

function getMimeTypeFromFileName(fileName) {
  var extension = fileName.split('.').pop().toLowerCase(); // Ottieni l'estensione del file
  var mimeTypeMap = {
    'jpg': 'image/jpeg',
    'jpeg': 'image/jpeg',
    'png': 'image/png',
    'gif': 'image/gif',
    'bmp': 'image/bmp',
    'webp': 'image/webp',
    'pdf': 'application/pdf',
    'txt': 'text/plain',
    'csv': 'text/csv',
    'html': 'text/html',
    'zip': 'application/zip',
    // Aggiungi altre mappature se necessario
  };

  return mimeTypeMap[extension] || 'application/octet-stream'; // Restituisce 'application/octet-stream' se l'estensione non è nella mappatura
}

function setTelegramWebhook() {
  var apiUrl = 'https://api.telegram.org/bot' + gTokenBot + '/setWebhook';
  var response = UrlFetchApp.fetch(apiUrl);
  Logger.log(response.getContentText());

  var apiUrl = 'https://api.telegram.org/bot' + gTokenBot + '/setWebhook?url=' + encodeURIComponent(gUrlDeployGoogleAppsScript);
  var response = UrlFetchApp.fetch(apiUrl);
  Logger.log(response.getContentText());
}
