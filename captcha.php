<?php

function generate_captcha(): array
{
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $answer = $num1 + $num2;
    $question = "$num1 + $num2";

    $_SESSION['captcha_answer'] = $answer;
    $_SESSION['captcha_question'] = $question;

    return [
        'question' => $question,
        'answer' => $answer
    ];
}

function get_captcha(): array
{
    if (empty($_SESSION['captcha_question']) || empty($_SESSION['captcha_answer'])) {
        return generate_captcha();
    }

    return [
        'question' => $_SESSION['captcha_question'],
        'answer' => $_SESSION['captcha_answer']
    ];
}

function verify_captcha($user_answer): bool
{
    $correct_answer = $_SESSION['captcha_answer'] ?? null;

    if ($correct_answer === null) {
        return false;
    }

    $user_answer = trim($user_answer ?? '');

    if ($user_answer === '' || !is_numeric($user_answer)) {
        return false;
    }

    $result = (int)$user_answer === (int)$correct_answer;
    
    if ($result) {
        unset($_SESSION['captcha_answer']);
        unset($_SESSION['captcha_question']);
    }

    return $result;
}

function reset_captcha(): void
{
    unset($_SESSION['captcha_answer']);
    unset($_SESSION['captcha_question']);
}
