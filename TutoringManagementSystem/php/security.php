<?php

function cleanInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars(
        $data,
        ENT_QUOTES,
        'UTF-8'
    );

    return $data;
}

function validateEmail($email)
{
    return filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    );
}

function hashPassword($password)
{
    return password_hash(
        $password,
        PASSWORD_DEFAULT
    );
}

function verifyPassword(
    $password,
    $hash
)
{
    return password_verify(
        $password,
        $hash
    );
}

?>
