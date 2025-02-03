<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiController extends AbstractController
{
    private HttpClientInterface $client;
    private string $geminiApiKey;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->geminiApiKey = $_ENV['GEMINI_API_KEY'];
    }

    #[Route('/gemini/generate', name: 'gemini_generate', methods: ['POST'])]
    public function generate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['ingredients']) || !is_array($data['ingredients'])) {
            return new JsonResponse(['error' => 'Paramètre "ingredients" manquant ou invalide'], 400);
        }
        $ingredientList = implode(', ', $data['ingredients']);
        $prompt = "Liste 5 recettes avec au moins : $ingredientList";

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$this->geminiApiKey}";
        $payload = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ]
        ];

        try {
            $response = $this->client->request('POST', $url, [
                'json' => $payload
            ]);
            $responseData = $response->toArray();
            return new JsonResponse($responseData);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de l\'appel API de gemini', 'message' => $e->getMessage()], 500);
        }
    }

}

