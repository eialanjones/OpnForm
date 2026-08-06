<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Model used to write the document
    |--------------------------------------------------------------------------
    |
    | Kept in config so switching models never requires a code change. The
    | model id is passed straight through to the OpenAI chat completions API.
    |
    */
    'model' => env('AI_PDF_MODEL', 'gpt-5.6-luna'),

    'temperature' => (float) env('AI_PDF_TEMPERATURE', 0.4),

    'max_output_tokens' => (int) env('AI_PDF_MAX_OUTPUT_TOKENS', 8192),

    /*
    |--------------------------------------------------------------------------
    | Knowledge sources and structure templates
    |--------------------------------------------------------------------------
    */
    'documents' => [
        // 100 MB per file.
        'max_file_size' => (int) env('AI_PDF_DOCUMENT_MAX_SIZE', 104857600),

        'max_knowledge_per_block' => (int) env('AI_PDF_MAX_KNOWLEDGE_PER_BLOCK', 5),

        'allowed_extensions' => ['pdf', 'xlsx', 'xls', 'csv', 'txt', 'md'],

        'allowed_mime_types' => [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/zip', // xlsx files are detected as zip by finfo
            'text/csv',
            'text/plain',
            'text/markdown',
        ],

        // Hard ceiling on what we keep from a single document.
        'max_extracted_characters' => (int) env('AI_PDF_MAX_EXTRACTED_CHARS', 200000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Prompt context budget
    |--------------------------------------------------------------------------
    |
    | Characters, not tokens. Deliberately conservative: a runaway knowledge
    | base would otherwise turn every submission into an expensive request.
    |
    */
    'context' => [
        'max_knowledge_characters' => (int) env('AI_PDF_MAX_KNOWLEDGE_CHARS', 120000),
        'max_template_characters' => (int) env('AI_PDF_MAX_TEMPLATE_CHARS', 20000),
        'max_answer_characters' => (int) env('AI_PDF_MAX_ANSWER_CHARS', 5000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery
    |--------------------------------------------------------------------------
    */
    // Minutes a signed download link stays valid.
    'download_url_ttl' => (int) env('AI_PDF_DOWNLOAD_URL_TTL', 30),

    // Generations (and their unsubmitted PDFs) are pruned after this many hours.
    'generation_retention_hours' => (int) env('AI_PDF_GENERATION_RETENTION_HOURS', 48),
];
