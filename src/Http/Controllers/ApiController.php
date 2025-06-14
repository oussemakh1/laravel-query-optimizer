<?php

namespace Vendor\QueryOptimizer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Vendor\QueryOptimizer\QueryAnalyzer;

class ApiController extends Controller
{
    public function metrics(QueryAnalyzer $analyzer)
    {
        return response()->json($analyzer->getStats());
    }


    public function explain(Request $request, QueryAnalyzer $queryAnalyzer)
    {
        $request->validate([
            'sql' => 'required|string',
        ]);
        $databaseInfo = [];

        $schemaManager = $queryAnalyzer->getDbal()?->createSchemaManager();
        $tables = $schemaManager->listTables();

        foreach ($tables as $table) {
            $tableName = $table->getName();
            $indexes = $table->getIndexes();

            $rowCount = DB::table($tableName)->count();
            $dataSize = DB::table($tableName)->count() * 100;

            $databaseInfo[] = [
                'table' => $tableName,
                'indexes' => array_keys($indexes),
                'row_count' => $rowCount,
                'data_mb_size' => $dataSize,
            ];
        }

        $databaseSystem = $schemaManager->getDatabasePlatform()->getName();
        $databaseVersion = $queryAnalyzer->getDbal()->getWrappedConnection()->getServerVersion();

        $database = [
            'tables' => json_encode($databaseInfo),
            'system' => $databaseSystem,
            'version' => $databaseVersion,
        ];

        $databaseInfo['system'] = $databaseSystem;
        $databaseInfo['version'] = $databaseVersion;
        $sql = $request->input('sql');
        $prompt = "You're a database expert. Explain  and tell me is there any improvement needed also the cost estimation the following SQL query:\n\n" . $sql . " this is the database info : " . json_encode($database) .  "retun the response in a clear manner and you can use html to improve the readability of the response.For cost estimation, assume a typical, generally available database server environment (e.g., a standard small-to-medium cloud-hosted instance or a modest VPS). Provide a general qualitative estimation of the monetary cost per operation, acknowledging the lack of specific infrastructure details.
        Return the response in a clear manner and you can use HTML and Markdown to improve the readability of the response. Please include a dedicated 'Cost Estimation' section";
        $apiKey = config('queryoptimizer.gemini_api_key');
        if (empty($apiKey)) {
            return response()->json(['error' => 'API key is missing'], 500);
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";
        $response = Http::post($url, [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ]
        ]);

        if ($response->failed()) {
            \Log::error('Gemini API Error: ' . $response->body());
            return response()->json(['error' => $response->json('error.message', json_encode($response))], 500);
        }

        $explanation = $response->json('candidates.0.content.parts.0.text');

        return response()->json([
            'explanation' => $explanation ?? 'No explanation available.'
        ]);
    }
}
