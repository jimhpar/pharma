<?php
namespace App\Traits;

use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Carbon\Carbon;

trait ImageTrait
{
    /**
     * Save the uploaded image in the given directory and return the location.
     *
     * @param string $folder_name
     * @param $image
     * @param int|null $width
     * @param int|null $height
     * @return string|null
     */
    public function save_image(string $folder_name, $image, int $width = null, int $height = null): ?string
    {
        if (isset($image)) {
            if (!File::isDirectory('public/' . $folder_name)) {
                File::makeDirectory(('public/' . $folder_name), 0777, true, true);
            }

            if ($width !== null && $height !== null) {
                $img = Image::make($image)->resize($width, $height);
            } else {
                $img = Image::make($image);
            }

            $img_extension = $image->getClientOriginalExtension();
            $location = 'public/' . $folder_name . '/' . uniqid('', false) . '.' . $img_extension;
            $img->save($location);
            return $location;
        }

        return null;
    }

    public function deleteImage($url): ?bool
    {
        if (isset($url)) {
            if (File::exists($url)) {
                File::delete($url);
                return true;
            }
            return false;
        }
        return null;
    }
}
