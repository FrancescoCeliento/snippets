<?php

/**
 * Lo script si occupa di monitorare un feed rss memorizzando l'ultimo link pubblicato.
 * Ogni volta che interrogando un feed rss riscontra novità, estrae solo le novità e le
 * pubblica sul un canale telegram configurato con il nostro bot.
 * Articolo di riferimento: https://www.selectallfromdual.com/blog/1669/aggiornare-un-canale-telegram-con-php
 */

$rssFeedUrl = '[URL_FEEDRSS]'; 
$storageFile = 'last_rss_url.txt';
$tokenBotTelegram = '[BOT_TELEGRAM_TOKEN]';
$handleChannelTelegram = '@[USERNAME_CHANNEL]';
// Remember to set up a cron job

class RssChecker {
    private string $rssUrl;
    private string $storageFilePath;

    public function __construct(string $rssUrl, string $storageFilePath) {
        $this->rssUrl = $rssUrl;
        $this->storageFilePath = $storageFilePath;
    }

    private function getStoredUrl(): ?string {
        if (!file_exists($this->storageFilePath)) {
            return null;
        }

        $content = file_get_contents($this->storageFilePath);
        if ($content === false) {
            return null;
        }

        $url = trim($content);
        return $url === '' ? null : $url;
    }

    private function updateStoredUrl(string $newUrl): bool {
        $result = file_put_contents($this->storageFilePath, $newUrl . "\n");
        return $result !== false;
    }

    private function fetchRssItems(): ?array {
        try {
            $xml = @simplexml_load_file($this->rssUrl);
            
            if ($xml === false) {
                error_log("Errore: Impossibile caricare il feed RSS dall'URL: " . $this->rssUrl);
                return null;
            }
            $items = [];
            
            foreach ($xml->channel->item as $item) {
                $items[] = $item;
            }
            usort($items, function ($a, $b) {
                $timeA = strtotime((string) $a->pubDate);
                $timeB = strtotime((string) $b->pubDate);
                return $timeB <=> $timeA; 
            });

            return $items;

        } catch (Exception $e) {
            error_log("Eccezione durante il caricamento del feed: " . $e->getMessage());
            return null;
        }
    }

    private function getLatestItemUrl(array $items): ?string {
        if (empty($items)) {
            return null;
        }


        $latestItem = $items[0];
        
        return (string) $latestItem->link ?: null;
    }
    
    public function checkForUpdates(): ?array {
        $items = $this->fetchRssItems();
        if ($items === null) {
            return null; // Fallimento nella lettura del feed
        }

        $latestRssUrl = trim($this->getLatestItemUrl($items));
        
        if ($latestRssUrl === null) {
            return [];
        }

        $storedUrl = trim($this->getStoredUrl());

        if ($storedUrl !== null && $latestRssUrl === $storedUrl) {
            return [];
        }
        

        $newItems = [];
        $updateFound = ($storedUrl === null);
        
        foreach ($items as $item) {
            $currentUrl = (string) $item->link;
            
            if ($storedUrl !== null && $currentUrl === $storedUrl) {
                break; 
            }

            $newItems[] = $item;

        }
        if (!empty($newItems) && $this->updateStoredUrl($latestRssUrl)) {
            return $newItems;
        } elseif (!empty($newItems)) {
            error_log("Avviso: Aggiornamento RSS trovato, ma errore nel salvare il nuovo URL: " . $latestRssUrl);
            return $newItems;
        } else {
            return []; 
        }
    }
}


function sendMessageTelegram($token, $channel, $message) {
    $api_url = "https://api.telegram.org/bot" . $token . "/sendMessage";

    $data = array(
        'chat_id' => $channel,
        'text'    => $message
    );

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);

    curl_close($ch);

    return json_decode($result, true);
}

$checker = new RssChecker($rssFeedUrl, $storageFile);

$newUpdates = $checker->checkForUpdates();

if ($newUpdates === null) {
    echo "Errore critico nel caricamento o processamento del feed RSS.<br>";
} elseif (empty($newUpdates)) {
    echo "Nessun nuovo aggiornamento trovato. L'ultimo URL è rimasto lo stesso.<br>";
} else {
    echo "Sono stati trovati " . count($newUpdates) . " NUOVI item!<br>";
    
    foreach ($newUpdates as $item) {
        $title = (string) $item->title;
        $description = (string) $item->description;
        $link = (string) $item->link;
        //$pubDate = (string) $item->pubDate;

        $messageTxt = $title."\n";
        $messageTxt.= $description."\n\n";
        $messageTxt.= $link;

        sendMessageTelegram($tokenBotTelegram,$handleChannelTelegram,$messageTxt);
    }
    
}

?>
