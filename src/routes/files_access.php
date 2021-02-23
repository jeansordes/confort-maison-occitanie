<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

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
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
    $filename = sprintf('%s.%0.8s', $basename, $extension);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}

$app->get('/uploads/{filename}', function (Request $request, Response $response, array $args): Response {
    // check in the db if the file exists
    $db = getPDO();
    $req = $db->prepare(getSqlQueryString('getfile'));
    
    $req->execute(['id_commercial' => $_SESSION['current_user']['uid']]);
    $result = $req->fetchAll();
    
    // if not, respond with 404 error
    $image = file_get_contents("image_location");
    return $response->withHeader("Content-Type", "image/jpeg")->write($image);

    return $response->write($this->view->render('settings/password-edit.html.twig', ['email' => $_SESSION['current_user']['email']]));
});
