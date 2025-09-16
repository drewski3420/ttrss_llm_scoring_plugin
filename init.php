<?php
class Llm_Scoring extends Plugin {
  private $host;
  protected $pdo;

  // -----------------------------
  // Plugin metadata
  // -----------------------------
  function about() {
    return [
      1.0,
      "Scores articles using an LLM and adds a Recommended Articles feed",
      "your_name"
    ];
  }

  function api_version() {
    return 2;
  }

  // -----------------------------
  // Initialization
  // -----------------------------
  function init($host) {
    $this->host = $host;

    // DB handle for custom table
    $this->pdo = $host->get_dbh();

    // Create table if not exists
    $this->pdo->exec("
      CREATE TABLE IF NOT EXISTS ttrss_llm_scores (
        article_id integer PRIMARY KEY,
        score real
      )
    ");

    // Hook to score articles
    $host->add_hook($host::HOOK_ARTICLE_FILTER, $this);

    // Hook to create virtual feed
    $host->add_hook($host::HOOK_CUSTOM_FEED, $this);

    // Hook to add feed to sidebar
    $host->add_hook($host::HOOK_PREFS_MENU, $this);
  }

  // -----------------------------
  // Article scoring
  // -----------------------------
  function hook_article_filter($article) {
    $score = $this->score_with_llm($article['title'], $article['content']);

    $article_id = $article['id'];

    // Store score in custom table
    $this->pdo->exec("
      INSERT INTO ttrss_llm_scores (article_id, score)
      VALUES (" . intval($article_id) . ", " . floatval($score) . ")
      ON CONFLICT (article_id) DO UPDATE SET score = EXCLUDED.score
    ");

    // Optional: show score in title for testing
    $article['title'] = "[Score: $score] " . $article['title'];

    return $article;
  }

  // -----------------------------
  // Virtual feed: Recommended Articles
  // -----------------------------
  function hook_custom_feed($feed_id) {
    if ($feed_id == 'recommended') {
      return [
        'title' => 'Recommended Articles',
        'query' => "
          SELECT e.*
          FROM ttrss_entries e
          LEFT JOIN ttrss_llm_scores s ON e.id = s.article_id
          WHERE e.published = 1
          ORDER BY s.score DESC, e.updated DESC
        ",
      ];
    }
    return null;
  }

  // -----------------------------
  // Add feed to sidebar
  // -----------------------------
  function hook_prefs_menu($menu) {
    $menu['special']['recommended'] = 'Recommended Articles';
    return $menu;
  }

  // -----------------------------
  // Dummy LLM scoring function
  // -----------------------------
  private function score_with_llm($title, $content) {
    // Replace this with your real LLM API call
    return rand(0, 100) / 10;  // 0.0 - 10.0 dummy score
  }
}
