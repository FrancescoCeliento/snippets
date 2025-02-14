function doPost(e) {
  try {
    if (e.postData) {
      var tokenBot = ''; // Sostituisci con il tuo token
      var update = JSON.parse(e.postData.contents);
      // Make sure this is update is a type message
      if (update.hasOwnProperty('message')) {
        var msg = update.message;
        var chatId = msg.chat.id;
        //sendMessageToTelegram(tokenBot, JSON.stringify(msg, null, 2), chatId);
        // Mi assicuro che si tratti di un comando
        if (msg.hasOwnProperty('entities') && msg.entities[0].type == 'bot_command') {
          // Inizio elenco comandi
          if (msg.text == '/start') {
            sendMessageToTelegram(tokenBot, 'Bot avviato correttamente', chatId);
          } else if (msg.text == '/whois') {
            sendMessageToTelegram(tokenBot, 'Il tuo chatid è ' + chatId, chatId);
          } else if (msg.text == '/version') {
            sendMessageToTelegram(tokenBot, 'Versione X' , chatId);
          }
          //Fine elenco comandi

        } else {
          
          //Se è un'immagine
          if (msg.photo) {
            sendMessageToTelegram(tokenBot, 'Rilevata ricezione immagine', chatId);
            // Telegram invia le foto come array di oggetti
            
          } //Se è un semplice messaggio di testo
            else if (msg.text && msg.text.length > 0) {
            sendMessageToTelegram(tokenBot, 'Rilevato ricezione testo', chatId);

            //Risponde con lo stesso testo ricevuto
            sendMessageToTelegram(tokenBot, 'Post pubblicato correttamente', chatId);
            
          } //SE non è niente allora niente
            else {
            sendMessageToTelegram(tokenBot, 'Niente', chatId);
              
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

function setTelegramWebhook() {
  var token = ''; // Sostituisci con il tuo token
  var url = ''; // Sostituisci con l'URL del tuo webhook

  var apiUrl = 'https://api.telegram.org/bot' + token + '/setWebhook';
  var response = UrlFetchApp.fetch(apiUrl);
  Logger.log(response.getContentText());

  var apiUrl = 'https://api.telegram.org/bot' + token + '/setWebhook?url=' + encodeURIComponent(url);
  var response = UrlFetchApp.fetch(apiUrl);
  Logger.log(response.getContentText());
}
