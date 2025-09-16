<?php
class Llm_Scoring extends Plugin {
  private $host;

  function about() {
    return [1.0, "Test plugin: prepend random LLM score and add threshold tags", "Drew"];
  }

  function api_version() {
    return 2;
  }
  function init($host) {
    $this->host = $host;
    $host->add_hook($host::HOOK_ARTICLE_FILTER, $this);
  }
  function hook_article_filter($article) {
    // Generate a random score 0.0 - 10.0
    $score = rand(0, 100) / 10;
    // Prepend score to title
    $article['title'] = "[LLM Score: $score] " . $article['title'];
    // Add threshold tags: score>1 ... score>floor(score)
    if (!isset($article['tags'])) {
      $article['tags'] = [];
    }
    $int_score = floor($score);
    for ($i = 1; $i <= $int_score; $i++) {
      $article['tags'][] = "score>$i";
    }
    return $article;
  }
}
