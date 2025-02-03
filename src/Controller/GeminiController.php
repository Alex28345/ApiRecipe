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
        // 🔹 Récupération des ingrédients envoyés
        $data = json_decode($request->getContent(), true);
        if (!isset($data['ingredients']) || !is_array($data['ingredients'])) {
            return new JsonResponse(['error' => 'Paramètre "ingredients" manquant ou invalide'], 400);
        }

        // 📝 Prompt pour Gemini
        $ingredientList = implode(', ', $data['ingredients']);
        $prompt = "Donne-moi une recette au format JSON contenant uniquement : 
        - `title` : le nom de la recette
        - `description` : une courte description
        - `steps` : une liste d'étapes pour préparer la recette
        - `ingredients` : une liste d'ingrédients avec leur quantité et unité si possible
        Les ingrédients obligatoires sont : $ingredientList. 
        Réponds uniquement avec du JSON valide, sans texte supplémentaire ni formatage en ```json```.";

        // 🌐 Requête API Gemini
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$this->geminiApiKey}";
        $payload = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ]
        ];

        try {
            $response = $this->client->request('POST', $url, ['json' => $payload]);
            $responseData = $response->toArray();

            // 📌 Extraction de la réponse textuelle de Gemini
            if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                return new JsonResponse(['error' => 'Réponse de Gemini invalide'], 500);
            }

            // 🛠 Nettoyage de la réponse : suppression des backticks et de ```json
            $rawJson = $responseData['candidates'][0]['content']['parts'][0]['text'];
            $cleanJson = preg_replace('/^```json\s*/', '', trim($rawJson)); // Supprime ```json et les espaces
            $cleanJson = preg_replace('/```$/', '', $cleanJson); // Supprime ``` à la fin

            // 🔍 Décodage du JSON propre
            $recipe = json_decode($cleanJson, true);

            // ✅ Vérification des champs requis
            if (!isset($recipe['title'], $recipe['description'], $recipe['steps'], $recipe['ingredients'])) {
                return new JsonResponse(['error' => 'Format de recette invalide'], 500);
            }

            // 📤 Réponse formatée avec ingrédients
            return new JsonResponse([
                'title' => $recipe['title'],
                'description' => $recipe['description'],
                'steps' => $recipe['steps'],
                'ingredients' => $recipe['ingredients']
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de l\'appel API de Gemini',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
