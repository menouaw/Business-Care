<?php

function buildChatCompletionRequest(array $messages, $stream = false) {
    return [
        "model" => MODEL_NAME,
        "stream" => $stream,
        "messages" => $messages
    ];
}
