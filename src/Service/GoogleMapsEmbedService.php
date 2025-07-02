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

        // Try to extract coordinates from /maps/search/lat,lng
        if (preg_match('#/maps/search/([\\d\\.\-]+)[,\+ ]+([\\d\\.\-]+)#', $finalUrl, $matches)) {
            $lat = $matches[1];
            $lng = $matches[2];
            return "https://www.google.com/maps?q=$lat,$lng&hl=fr&z=16&output=embed";
        }
        // Try to extract coordinates from /@lat,lng,zoomz
        if (preg_match('#/maps/@([\\d\\.\-]+),([\\d\\.\-]+),#', $finalUrl, $matches)) {
            $lat = $matches[1];
            $lng = $matches[2];
            return "https://www.google.com/maps?q=$lat,$lng&hl=fr&z=16&output=embed";
        }
        // Try to extract place name from /maps/place/PLACE_NAME
        if (preg_match('#/maps/place/([^/]+)#', $finalUrl, $matches)) {
            $place = urldecode($matches[1]);
            $place = str_replace(['+', '%20'], ' ', $place);
            $place = trim($place);
            $place = urlencode($place);
            return "https://www.google.com/maps?q=$place&hl=fr&z=16&output=embed";
        }
        // Try to extract coordinates from ?q=lat,lng
        if (preg_match('#[?&]q=([\\d\\.\-]+),([\\d\\.\-]+)#', $finalUrl, $matches)) {
            $lat = $matches[1];
            $lng = $matches[2];
            return "https://www.google.com/maps?q=$lat,$lng&hl=fr&z=16&output=embed";
        }
        // Try to extract place name from ?q=PLACE_NAME
        if (preg_match('#[?&]q=([^&]+)#', $finalUrl, $matches)) {
            $place = urldecode($matches[1]);
            $place = str_replace(['+', '%20'], ' ', $place);
            $place = trim($place);
            $place = urlencode($place);
            return "https://www.google.com/maps?q=$place&hl=fr&z=16&output=embed";
        }
        return null;
    }
}
