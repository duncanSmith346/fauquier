<?php



class Question {
    private $application_id;
    private $type;
    private $question;
    private $answer;
    private $answers;
    private $comment;
}

function __construct($id,$type,$question,$answers,) {
    $this->application_id = $id;
    $this->type = $type;
    $this->question = $question;
    $this->answers = $answers;
}

function get_answers() {
    return $this->answers;
}

function get_question() {
    return $this->question;
}

function get_comment() {
    return $this->comment;
}

function set_comment($comment) {
    $this->comment = $comment;
}

function get_type() {
    return $this->type;
}

function get_answer() {
    return $this->answer;
}

function set_answer($answer) {
    $this->answer = $answer;
}