<?php

$app->get('/', function ($request, $response, $args) {
    if (!empty($_GET['action']) and !empty($_GET['token'])) {
        // => there is a token with an associated action
        // vérifier que l'action est valide
        if (!in_array($_GET['action'], ['reset_password', 'init_password', 'mail_login'])) {
            throw new Exception("'?action=" . $_GET['action'] . "' non traitable");
        }

        // vérifier le token (et récupérer les infos utiles au cas où)
        $payload = jwt_decode($_GET['token']);
        $db = getPDO();
        $req = $db->prepare("select last_time_settings_changed, user_role, prenom, nom_famille from user_account, user where user_id = :uid and id = user_id");
        $req->execute(['uid' => $payload['uid']]);
        $res = $req->fetch();
        $user_infos = [
            'uid' => $payload['uid'],
            'prenom' => $res['prenom'],
            'nom_famille' => $res['nom_famille'],
            'user_role' => $res['user_role'],
        ];
        if ($res['last_time_settings_changed'] != $payload['last_time_settings_changed']) {
            throw new Exception("Ce lien n'est plus valide (votre compte a été modifié depuis l'émission de ce lien)");
        }

        // connecter l'utilisateur
        $_SESSION['current_user'] = $user_infos;

        // montrer la bonne page
        if (in_array($_GET['action'], ['reset_password', 'init_password'])) {
            return $response->withRedirect('/password-edit');
        } else if (in_array($_GET['action'], ['mail_login'])) {
            return $response->withRedirect('/');
        }
    } else if (empty($_SESSION['current_user'])) {
        // not logged => /login
        return $response->withRedirect(empty($_GET['redirect']) ? '/login' : $_GET['redirect']);
    } else {
        // logged in => /commerciaux
        return $response->withRedirect(empty($_GET['redirect']) ? '/commerciaux' : $_GET['redirect']);
    }
});

$app->get('/login', function ($request, $response, $args) {
    if (empty($_SESSION['current_user'])) {
        return $this->view->render('signin/login.html.twig');
    } else {
        return $response->withRedirect(empty($_GET['redirect']) ? '/' : $_GET['redirect']);
    }
});

$app->post('/login', function ($request, $response) {
    // verifier login + mdp, si oui, mettre dans $_SESSION['current_user'] : user_role + user_id + prenom + nom
    if (empty($_POST['email']) or empty($_POST['password'])) {
        throw new Exception("Il manque un des champs suivants [email, password]");
    }
    $db = getPDO();
    $req = $db->prepare('select id, prenom, nom_famille, user_role, password_hash from user, user_account where email = :email and user_id = id');
    $req->execute(['email' => $_POST['email']]);
    if ($req->rowCount() == 0) {
        throw new Exception("Cet email est inconnu");
    } else {
        // get password hash (and infos at the same time)
        $res = $req->fetch();
        $user_infos = [
            'uid' => $res['id'],
            'prenom' => $res['prenom'],
            'nom_famille' => $res['nom_famille'],
            'user_role' => $res['user_role'],
        ];

        // check password
        if (password_verify($_POST['password'], $res['password_hash'])) {
            // create session
            $_SESSION['current_user'] = $user_infos;
            // redirect
            return $response->withRedirect(empty($_GET['redirect']) ? '/' : $_GET['redirect']);
        } else {
            throw new Exception("Mot de passe incorrect");
        }
    }
});

$app->get('/password-reset', function ($request, $response, $args) {
    return $this->view->render('signin/password-reset.html.twig');
});

$app->post('/password-reset', function ($request, $response) {
    if (!empty($_POST['email'])) {
        // faire des tests pour vérifier que l'email renseigné existe
        $db = getPDO();
        $req = $db->prepare('select id from user where email = :email');
        $req->execute(['email' => $_POST['email']]);
        if ($req->rowCount() == 0) {
            alert("Cet email est inconnu", 3);
            return $response->withRedirect('/password-reset');
        } else {
            $user_id = $req->fetch()['id'];
            // générer un token pour que l'utilisateur puisse réinitialiser son mot de passe
            $req = $db->prepare('select last_time_settings_changed from user_account where user_id = :user_id');
            $req->execute(['user_id' => $user_id]);
            $reponse = $req->fetch();
            console_log([$reponse, 'user_id' => $user_id]);

            $jwt = jwt_encode([
                "last_time_settings_changed" => $reponse['last_time_settings_changed'],
                "uid" => $user_id,
            ], 20);

            console_log($jwt);

            sendEmail(
                $_POST['email'],
                "Confort maison occitanie : Vous avez oublié votre de mot de passe ?",
                $this->view->render(
                    'emails/password-reset.html.twig',
                    ['url' => 'http://' . $_SERVER["SERVER_NAME"] . '/?action=reset_password&token=' . $jwt]
                )
            );

            alert("Un email avec un lien pour réinitialiser votre mot de passe vous a été envoyé", 1);
            return $response->withRedirect('/login');
        }
    } else {
        alert('Le champs "email" est manquant', 3);
        return $response->withRedirect('/password-reset');
    }
});

$app->get('/logout', function ($request, $response, $args) {
    session_destroy();
    return $response->withRedirect(empty($_GET['redirect']) ? '/' : $_GET['redirect']);
});
