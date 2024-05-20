<?php

namespace App\Service;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use function PHPUnit\Framework\fileExists;

class PictureService
{


    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function add(UploadedFile $picture, ?string $folder = '', ?int $width = 250, ?int $height = 250)
    {
        // je donne un nouveau nom à l'image :
        $fichier = md5(uniqid(rand(), true)) . '.webp';

        // je recupere les infos de l'image :
        $picture_infos = getimagesize($picture);

        if ($picture_infos === false) {
            throw new Exception('Format d\'image incorrect');
        }

        // je verifie le format de l'image : 
        switch ($picture_infos['mime']) {
            case 'image/png':
                // je récupère l'image dans une variable pour pouvoir la manipuler
                $picture_source = imagecreatefrompng($picture);
                break;
            case 'image/jpeg':
                // je récupère l'image dans une variable pour pouvoir la manipuler
                $picture_source = imagecreatefromjpeg($picture);
                break;
            case 'image/webp':
                // je récupère l'image dans une variable pour pouvoir la manipuler
                $picture_source = imagecreatefromwebp($picture);
                break;
            default:
                // je crée une exception pour les formats d'image incorrect
                throw new Exception('Format d\'image incorrect');
        }

        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($picture); // Lire les métadonnées EXIF
            if ($exif && isset($exif['Orientation'])) { // S'assurer qu'il y a des données EXIF
                switch ($exif['Orientation']) {
                    case 3: // 180 degrés
                        $picture_source = imagerotate($picture_source, 180, 0);
                        break;
                    case 6: // 90 degrés sens horaire
                        $picture_source = imagerotate($picture_source, 270, 0);
                        break;
                    case 8: // 90 degrés sens antihoraire
                        $picture_source = imagerotate($picture_source, 90, 0);
                        break;
                }
            }
        }

        // JE RECADRE L'IMAGE 
        // JE RECUPERE LES DIMENSION
        $imageWidth = $picture_infos[0];
        $imageHeight = $picture_infos[1];

        // ON VERIFIE L'ORIENTATION DE L'IMAGE 
        switch ($imageWidth <=> $imageHeight) {
            case -1: // si largeur inferieur a hauteur = portrait
                $squareSize = $imageWidth;
                $src_x = 0; // on reste en pleine largeur 
                $src_y = ($imageHeight - $squareSize) / 2; // on descend de la moitie de la hauteur pour la decoupe 
                break;
            case 0: //  carré 
                $squareSize = $imageWidth;
                $src_x = 0;
                $src_y = 0;
                break;
            case 1: // paysage 
                $squareSize = $imageHeight;
                $src_x = ($imageWidth - $squareSize) / 2; // on descend de la moitie de la hauteur pour la decoupe 
                $src_y = 0; // inverse de la pleine largeur
                break;
        }
        // ON CREE UNE NOUVELLE IMAGE VIERGE 
        $resized_picture = imagecreatetruecolor($width, $height);

        imagecopyresampled($resized_picture, $picture_source, 0, 0, $src_x, $src_y, $width, $height, $squareSize, $squareSize);

        $path = $this->params->get('images_directory') . $folder;

        // ON CREE LE DOSSIER DE DESTINATION S'IL N'EXISTE PAS 
        if (!file_exists($path . '/mini/')) {
            mkdir($path . '/mini/', 0755, true);
        }

        // JE STOCK L'IMAGE RECADRER
        imagewebp($resized_picture, $path . '/mini/' . $width . 'x' .
            $height . '-' . $fichier);

        $picture->move($path . '/', $fichier);

        return $fichier;
    }

    public function delete(string $fichier, ?string $folder = '', ?int $width = 250, ?int $height = 250)
    {

        if ($fichier !== 'default.webp') {
            $success = false;
            $path = $this->params->get('images_directory') . $folder;

            $mini = $path . '/mini/' . $width . 'x' .
                $height . '-' . $fichier;

            if (file_exists($mini)) {
                unlink($mini);
                $success = true;
            }

            $original = $path . '/' . $fichier;
            if (file_exists($original)) {
                unlink($original);
                $success = true;
            }
            return $success;
        }

        return false;
    }
}
