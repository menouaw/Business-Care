<?php

function buildChatCompletionRequest(array $userMessages, bool $stream = false) {
    $formattedContents = [];
    foreach ($userMessages as $msg) {
        
        if (isset($msg['role'], $msg['text_content']) && trim($msg['text_content']) !== '') {
            $role = $msg['role'];
            
            
            if ($role === 'assistant') {
                $role = 'model';
            }

            $formattedContents[] = [
                'role' => $role, 
                'parts' => [['text' => $msg['text_content']]]
            ];
        }
    }

    
    
    

    $request = [
        "contents" => $formattedContents,
        "systemInstruction" => [
            "parts" => [
                [
                    "text" => "Business Care (BC) est une société créée à Paris en 2018 qui propose une solution visant à améliorer la santé, le bien-être et la cohésion en milieu professionnel. Son objectif principal est d'avoir un impact positif sur la qualité de vie des salariés et de dynamiser le bien-être des équipes au sein des entreprises clientes. BC a connu une croissance rapide grâce à la qualité et à la diversité de ses prestations.\nTu es un assistant pour les clients de Business Care, si la question n'a rien à voir avec Business Care, réponds avec un message d'excuse."
                ]
            ]
        ],
        "generationConfig" => [
            "responseMimeType" => "text/plain",
            "temperature" => 0.5,
            "maxOutputTokens" => 256,
            "topP" => 0.5,
            "seed" => 0,
        ],
        "safetySettings" => [
            ["category" => "HARM_CATEGORY_HATE_SPEECH", "threshold" => "BLOCK_NONE"], 
            ["category" => "HARM_CATEGORY_DANGEROUS_CONTENT", "threshold" => "BLOCK_NONE"],
            ["category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT", "threshold" => "BLOCK_NONE"],
            ["category" => "HARM_CATEGORY_HARASSMENT", "threshold" => "BLOCK_NONE"]
        ]
    ];


    return $request;
}
