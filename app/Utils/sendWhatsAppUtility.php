<?php

namespace App\Utils;

use Illuminate\Support\Facades\Http;

class sendWhatsAppUtility
{
    public static function sendWhatsApp($customer, $params, $media, $campaignName)
    {
        // Check if the service is enabled in the environment configuration
       
            // Prepare the message content
            $content = array();
            $content['messaging_product'] = "whatsapp";
            $content['to'] = $customer;
            $content['type'] = 'template';
            $content['template'] = $params;

            // Add media if required
            if ($media) {
                $content['media'] = $media;
            }

            // Get API token from environment variables
            $token = "EAAXN2hZCQf8QBO9Y1AmZCXJKoi8YoSA4wtUaAXOCVD7vNOc7HeIFe9qiDBeDfuH0I9poQQ4kZBzbFKEQfFcggz6gUZCjbJM8IyTTbg5aYsHytkLDPzZCDV2gKOiJFd0ZBhXalLkUzMqDZAwpChMq1ErBLm5ft6iNdnHIPO8WzpZCJmC9BXcX9tIJ7gx6IWr79hWtxQZDZD";


            // Initialize cURL request
            $curl = curl_init();

            // Initialize $response to null as a default
            $response = null;

            // Set cURL options for the request
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://graph.facebook.com/v20.0/441136079088316/messages',  // Replace with the actual API endpoint
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($content),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $token
                ),
            ));

            // Execute the cURL request and store the response
            $response = curl_exec($curl);

            // Close the cURL session
            curl_close($curl);
        

        // Return the response from the cURL request
        return $response;
    }
    
}