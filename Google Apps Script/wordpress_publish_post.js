function publishPostOnWordpress(domain, titleText, contentText) {
  var url = domain + '/wp-json/wp/v2/posts'; // Sostituisci con l'URL del tuo sito
  var username = ''; // Sostituisci con il tuo username
  var password = ''; // Sostituisci con la tua password o password dell'app

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
      'Authorization': 'Basic ' + Utilities.base64Encode(username + ':' + password)
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
  var postUrl = domain + 'wp-json/wp/v2/posts'; // Endpoint per pubblicare post
  var imageBlob = UrlFetchApp.fetch(imageUrl).getBlob();
  var imageName = imageUrl.split('/').pop();
  var mimeType = getMimeTypeFromFileName(imageName);

  var mediaOptions = {
    method: 'post',
    contentType: mimeType,
    payload: imageBlob,
    headers: {
      'Authorization': 'Basic ' + Utilities.base64Encode(username + ':' + password),
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
