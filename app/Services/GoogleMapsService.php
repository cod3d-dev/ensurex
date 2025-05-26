<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleMapsService
{
    /**
     * Get location data from a zip code using Google Maps Geocoding API
     *
     * @param string $zipCode
     * @return array|null
     */
    public function getLocationFromZipCode(string $zipCode): ?array
    {
        try {
            $apiKey = config('google.maps.geocoding_api_key');
            
            if (empty($apiKey)) {
                Log::warning('Google Maps API key is not set');
                return null;
            }

            $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $zipCode,
                'key' => $apiKey,
                'components' => 'country:US', // Restrict to US addresses
            ]);

            if (!$response->successful()) {
                Log::error('Google Maps API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();

            if ($data['status'] !== 'OK' || empty($data['results'])) {
                return null;
            }

            return $this->parseLocationData($data['results'][0]);
        } catch (\Exception $e) {
            Log::error('Error fetching location data from Google Maps API', [
                'error' => $e->getMessage(),
                'zipCode' => $zipCode,
            ]);
            return null;
        }
    }

    /**
     * Parse the Google Maps Geocoding API response to extract city, state, and county
     *
     * @param array $result
     * @return array
     */
    private function parseLocationData(array $result): array
    {
        $locationData = [
            'city' => '',
            'state' => '',
            'county' => '',
        ];

        // Extract address components
        foreach ($result['address_components'] as $component) {
            $types = $component['types'];

            if (in_array('locality', $types)) {
                $locationData['city'] = $component['long_name'];
            } elseif (in_array('administrative_area_level_1', $types)) {
                $locationData['state'] = $component['short_name']; // Use short_name for state abbreviation
            } elseif (in_array('administrative_area_level_2', $types)) {
                $locationData['county'] = $component['long_name'];
                // Remove "County" suffix if present
                $locationData['county'] = preg_replace('/ County$/', '', $locationData['county']);
            }
        }

        return $locationData;
    }
}
