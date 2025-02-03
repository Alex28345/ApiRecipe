<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OllamaController extends AbstractController
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    #[Route('/ollama/generate', name: 'ollama_generate', methods: ['POST'])]
    public function generate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['model']) || !isset($data['prompt'])) {
            return new JsonResponse(['error' => 'Missing model or prompt'], 400);
        }

        // Appel à Ollama
        $response = $this->client->request('POST', 'http://localhost:11434/api/generate', [
            'json' => [

                'model' => $data['model'],
                'prompt' => $data['prompt'],
                'stream' => false
            ]
        ]);

        $content = $response->toArray();

        return new JsonResponse($content);
    }
}

