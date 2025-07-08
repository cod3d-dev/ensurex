<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ZipCodeService
{
    /**
     * Path to the CSV file containing zipcode data
     */
    protected string $csvFilePath;

    /**
     * Cache duration in minutes
     */
    protected int $cacheDuration = 1440; // 24 hours

    public function __construct()
    {
        $this->csvFilePath = storage_path('app/zipcodes.csv');
    }

    /**
     * Get location data from a zipcode
     *
     * @param string $zipCode
     * @return array|null Returns an array with city, state, and county, or null if not found
     */
    public function getLocationFromZipCode(string $zipCode): ?array
    {
        // Try to get from cache first
        $cacheKey = "zipcode_{$zipCode}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // If not in cache, look it up in the CSV
        $result = $this->lookupZipCodeInCsv($zipCode);

        // Store in cache if found
        if ($result) {
            Cache::put($cacheKey, $result, $this->cacheDuration * 60);
        }

        return $result;
    }

    /**
     * Look up zipcode information in the CSV file
     *
     * @param string $zipCode
     * @return array|null
     */
    protected function lookupZipCodeInCsv(string $zipCode): ?array
    {
        if (!file_exists($this->csvFilePath)) {
            Log::error("ZipCode CSV file not found at: {$this->csvFilePath}");
            return null;
        }

        try {
            // Open the CSV file
            $file = fopen($this->csvFilePath, 'r');
            if (!$file) {
                Log::error("Could not open ZipCode CSV file");
                return null;
            }

            // Skip the header row
            fgetcsv($file, 0, ';');
            
            // Normalize the zipcode by removing leading zeros
            $normalizedZipCode = ltrim($zipCode, '0');
            
            // Search for the zipcode
            while (($data = fgetcsv($file, 0, ';')) !== false) {
                // Normalize the CSV zipcode too
                $csvZipCode = ltrim($data[0], '0');
                
                // Check if the first column (zipcode) matches
                if ($csvZipCode === $normalizedZipCode) {
                    fclose($file);
                    return [
                        'city' => $data[1],       // Official USPS city name
                        'state' => $data[2],      // Official USPS State Code
                        'county' => $data[9],     // Primary Official County Name
                        'state_name' => $data[3], // Official State Name
                    ];
                }
            }

            fclose($file);
            return null;
        } catch (\Exception $e) {
            Log::error("Error looking up zipcode: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Set a custom path for the CSV file
     *
     * @param string $path
     * @return $this
     */
    public function setCsvFilePath(string $path): self
    {
        $this->csvFilePath = $path;
        return $this;
    }

    /**
     * Set the cache duration in minutes
     *
     * @param int $minutes
     * @return $this
     */
    public function setCacheDuration(int $minutes): self
    {
        $this->cacheDuration = $minutes;
        return $this;
    }
}
