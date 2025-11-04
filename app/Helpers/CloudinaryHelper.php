<?php

// namespace App\Helpers;

// use Cloudinary\Cloudinary;
// use Cloudinary\Configuration\Configuration;
// use Cloudinary\Api\Upload\UploadApi;

// class CloudinaryHelper
// {
//     protected $cloudinary;

//     public function __construct()
//     {
//         $config = config('services.cloudinary');
//         if (!$config['cloud_name'] || !$config['api_key'] || !$config['api_secret']) {
//             throw new \Exception('Cloudinary configuration is missing. Please check your .env file.');
//         }

//         Configuration::instance([
//             'cloud' => [
//                 'cloud_name' => $config['cloud_name'],
//                 'api_key' => $config['api_key'],
//                 'api_secret' => $config['api_secret']
//             ],
//             'url' => [
//                 'secure' => $config['secure']
//             ]
//         ]);

//         $this->cloudinary = new Cloudinary();
//     }


namespace App\Helpers;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Exception;
use Illuminate\Support\Facades\Log;

class CloudinaryHelper
{
    protected $cloudinary;

    public function __construct()
    {
        try {
            // Use config() instead of env() for better reliability
            $config = [
                'cloud_name' => config('services.cloudinary.cloud_name'),
                'api_key' => config('services.cloudinary.api_key'),
                'api_secret' => config('services.cloudinary.api_secret'),
            ];

            // Validate configuration
            foreach ($config as $key => $value) {
                if (empty($value)) {
                    throw new Exception("Missing Cloudinary $key configuration");
                }
            }

            Configuration::instance([
                'cloud' => $config,
                'url' => [
                    'secure' => true
                ]
            ]);

            $this->cloudinary = new Cloudinary();
        } catch (Exception $e) {
            Log::error('Cloudinary initialization failed: ' . $e->getMessage());
            throw new Exception('Cloudinary service unavailable. Please check configuration.');
        }
    }

    public function upload($file, $folder = '', $publicId = null)
    {
        try {
            // Determine the file path to upload - handle string or object
            if (is_string($file)) {
                // $file is a string path
                $filePath = $file;
            } elseif (is_object($file) && method_exists($file, 'getRealPath')) {
                // $file is an UploadedFile or similar object
                $filePath = $file->getRealPath();
            } else {
                throw new Exception('Invalid file parameter provided for upload.');
            }

            $options = [
                'folder' => $folder,
                'resource_type' => 'auto',
                'use_filename' => true,
                'unique_filename' => false,
            ];

            if ($publicId !== null) {
                $options['public_id'] = $publicId;
                $options['overwrite'] = true;
                $options['invalidate'] = true;
            }

            return (new UploadApi())->upload(
                $filePath,
                $options
            );
        } catch (Exception $e) {
            Log::error('Cloudinary upload error: ' . $e->getMessage());
            throw new Exception('File upload failed: ' . $e->getMessage());
        }
    }

    // public function upload($file, $folder = '', $publicId = null)
    // {
    //     try {
    //         $options = [
    //             'folder' => $folder,
    //             'resource_type' => 'auto',
    //             'use_filename' => true,
    //             'unique_filename' => false,
    //         ];

    //         // If public_id is provided, overwrite the file on Cloudinary
    //         if ($publicId !== null) {
    //             $options['public_id'] = $publicId;
    //             $options['overwrite'] = true;
    //             $options['invalidate'] = true; // Invalidate CDN cache for immediate update
    //         }

    //         return (new UploadApi())->upload(
    //             $file->getRealPath(),
    //             $options
    //         );
    //     } catch (Exception $e) {
    //         Log::error('Cloudinary upload error: ' . $e->getMessage());
    //         throw new Exception('File upload failed: ' . $e->getMessage());
    //     }
    // }

    // public function upload($file, $folder = '')
    // {
    //     try {
    //         return (new UploadApi())->upload(
    //             $file->getRealPath(),
    //             [
    //                 'folder' => $folder,
    //                 'resource_type' => 'auto',
    //                 'use_filename' => true,
    //                 'unique_filename' => false
    //             ]
    //         );
    //     } catch (Exception $e) {
    //         Log::error('Cloudinary upload error: ' . $e->getMessage());
    //         throw new Exception('File upload failed: ' . $e->getMessage());
    //     }
    // }


    public function destroy($publicId, $resourceType = null, $extension = null)
    {
        $upload = new UploadApi();
        try {
            $types = $resourceType ? [$resourceType] : ['raw', 'image', 'video'];
            $ids = [$publicId];
            if ($extension) {
                $ids[] = $publicId . '.' . $extension; // try with extension if needed
            }

            $last = null;
            $attempt = 0;
            foreach ($types as $type) {
                foreach ($ids as $id) {
                    $attempt++;
                    // Add invalidate to ensure CDN cache clears
                    $response = $upload->destroy($id, ['resource_type' => $type, 'invalidate' => true]);
                    Log::info("Cloudinary destroy attempt #{$attempt} public_id={$id} resource_type={$type} result=" . json_encode($response));
                    if (is_array($response) && ($response['result'] ?? null) === 'ok') {
                        return $response;
                    }
                    $last = $response;
                    // brief backoff between attempts
                    usleep(200 * 1000); // 200ms
                }
            }
            return $last;
        } catch (Exception $e) {
            Log::error('Cloudinary destroy error: ' . $e->getMessage() . ' for public_id ' . $publicId);
            throw $e;
        }
    }

    public function generateUrl($publicId, $options = [])
    {
        return $this->cloudinary->image($publicId)->toUrl();
    }
}
