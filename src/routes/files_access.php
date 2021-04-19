<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

require_once __DIR__ . "/../sql-utilities.php";

/**
 * Moves the uploaded file to the upload directory and assigns it a unique name
 * to avoid overwriting an existing uploaded file.
 *
 * @param string $directory directory to which the file is moved
 * @param UploadedFile $uploadedFile file uploaded file to move
 * @return string filename of moved file
 */
function moveUploadedFile($directory, UploadedFile $uploadedFile)
{
    $path_infos = pathinfo($uploadedFile->getClientFilename());

    if (strlen($path_infos['filename']) > 200) {
        $filename = substr(
            $path_infos['filename'],
            0,
            strpos(wordwrap(
                $path_infos['filename'],
                200
            ), "\n")
        );
    } else {
        $filename = $path_infos['filename'];
    }
    do {
        // see http://php.net/manual/en/function.random-bytes.php
        $filename .= '-' . bin2hex(random_bytes(5)) . '.' . $path_infos['extension'];

        $db = getPDO();
        $req = $db->prepare(getSqlQueryString('count_file'));
        $req->execute(['file_name' => $filename]);
        $count = $req->fetchColumn();
        // vérifier que le nom de fichier n'existe pas déjà en BDD
    } while ($count > 0);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}
