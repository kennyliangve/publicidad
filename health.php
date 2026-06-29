<?php
/**
 * 健康检查入口（独立访问，无需路由）
 * 访问: /publicidad/health.php 或 /publicidad/api/health
 */
require_once __DIR__ . '/api/routes/health.php';
handleHealth();
