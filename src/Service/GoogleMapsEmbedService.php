<?php
namespace App\Service;

class GoogleMapsEmbedService
{
    public function getEmbedUrlFromShortUrl(string $shortUrl): ?string
    {
        $ch = curl_init($shortUrl);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (preg_match('#/maps/search/([\\d\\.\\-]+)[,\\+ ]+([\\d\\.\\-]+)#', $finalUrl, $matches)) {            
            $lat = $matches[1];
            $lng = $matches[2];
            return "https://www.google.com/maps?q=$lat,$lng&hl=fr&z=16&output=embed";
        }
        if (preg_match('#/maps/search/([\\d\\.\\-]+)[,\\+ ]+([\\d\\.\\-]+)#', $finalUrl, $matches)) {            
            $lat = $matches[1];
            $lng = $matches[2];
            return "https://www.google.com/maps?q=$lat,$lng&hl=fr&z=16&output=embed";
        }
        return null;
    }
}
