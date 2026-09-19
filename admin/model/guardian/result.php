<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian;

use Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult;
/**
 * Class Result
 *
 * Persistent store for check runs, shared by all domains: one row per run in
 * gtr_guardian_run, one row per executed check in gtr_guardian_result.
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian
 */
class Result extends \Opencart\System\Engine\Model {
	public const RUN_RUNNING  = 'running';
	public const RUN_FINISHED = 'finished';

	/**
	 * @return void
	 */
	public function createTables(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "gtr_guardian_run` (
		  `run_id` int(11) NOT NULL AUTO_INCREMENT,
		  `domain` varchar(32) NOT NULL,
		  `origin` varchar(16) NOT NULL,
		  `status` varchar(16) NOT NULL,
		  `critical` int(11) NOT NULL DEFAULT '0',
		  `warning` int(11) NOT NULL DEFAULT '0',
		  `info` int(11) NOT NULL DEFAULT '0',
		  `errors` int(11) NOT NULL DEFAULT '0',
		  `checks_total` int(11) NOT NULL DEFAULT '0',
		  `date_started` datetime NOT NULL,
		  `date_finished` datetime DEFAULT NULL,
		  PRIMARY KEY (`run_id`),
		  KEY `domain_date` (`domain`, `date_started`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "gtr_guardian_result` (
		  `result_id` int(11) NOT NULL AUTO_INCREMENT,
		  `run_id` int(11) NOT NULL,
		  `category` varchar(32) NOT NULL,
		  `code` varchar(64) NOT NULL,
		  `severity` varchar(16) NOT NULL,
		  `status` varchar(16) NOT NULL,
		  `count` int(11) NOT NULL DEFAULT '0',
		  `items` mediumtext NOT NULL,
		  `message` text NOT NULL,
		  PRIMARY KEY (`result_id`),
		  KEY `run_id` (`run_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	}

	/**
	 * @return void
	 */
	public function dropTables(): void {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "gtr_guardian_result`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "gtr_guardian_run`");
	}

	/**
	 * Open a run; it stays in RUN_RUNNING until finishRun() is called.
	 *
	 * @param string $domain
	 * @param string $origin       who started it, e.g. "manual"
	 * @param int    $checks_total
	 *
	 * @return int run_id
	 */
	public function addRun(string $domain, string $origin, int $checks_total): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "gtr_guardian_run` SET `domain` = '" . $this->db->escape($domain) . "', `origin` = '" . $this->db->escape($origin) . "', `status` = '" . self::RUN_RUNNING . "', `checks_total` = '" . (int)$checks_total . "', `date_started` = NOW()");

		return $this->db->getLastId();
	}

	/**
	 * Close a run with its aggregated counters.
	 *
	 * @param int                $run_id
	 * @param array<string, int> $counts keys: critical, warning, info, errors
	 *
	 * @return void
	 */
	public function finishRun(int $run_id, array $counts): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "gtr_guardian_run` SET `status` = '" . self::RUN_FINISHED . "', `critical` = '" . (int)($counts['critical'] ?? 0) . "', `warning` = '" . (int)($counts['warning'] ?? 0) . "', `info` = '" . (int)($counts['info'] ?? 0) . "', `errors` = '" . (int)($counts['errors'] ?? 0) . "', `date_finished` = NOW() WHERE `run_id` = '" . (int)$run_id . "'");
	}

	/**
	 * @param int                                                          $run_id
	 * @param string                                                       $category
	 * @param \Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult $result
	 *
	 * @return void
	 */
	public function addResult(int $run_id, string $category, CheckResult $result): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "gtr_guardian_result` SET `run_id` = '" . (int)$run_id . "', `category` = '" . $this->db->escape($category) . "', `code` = '" . $this->db->escape($result->code) . "', `severity` = '" . $this->db->escape($result->severity) . "', `status` = '" . $this->db->escape($result->status) . "', `count` = '" . (int)$result->count . "', `items` = '" . $this->db->escape(json_encode($result->items)) . "', `message` = '" . $this->db->escape($result->message) . "'");
	}

	/**
	 * Latest finished run of a domain, [] if none.
	 *
	 * @param string $domain
	 *
	 * @return array<string, mixed>
	 */
	public function getLatestRun(string $domain): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "gtr_guardian_run` WHERE `domain` = '" . $this->db->escape($domain) . "' AND `status` = '" . self::RUN_FINISHED . "' ORDER BY `run_id` DESC LIMIT 1");

		return $query->row;
	}

	/**
	 * @param string $domain
	 * @param int    $limit
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getRuns(string $domain, int $limit = 10): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "gtr_guardian_run` WHERE `domain` = '" . $this->db->escape($domain) . "' ORDER BY `run_id` DESC LIMIT " . (int)$limit);

		return $query->rows;
	}

	/**
	 * Results of a run with the items JSON decoded.
	 *
	 * @param int $run_id
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getResults(int $run_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "gtr_guardian_result` WHERE `run_id` = '" . (int)$run_id . "' ORDER BY `result_id` ASC");

		$results = [];

		foreach ($query->rows as $row) {
			$row['items'] = $row['items'] ? (array)json_decode($row['items'], true) : [];

			$results[] = $row;
		}

		return $results;
	}

	/**
	 * Number of runs across all domains.
	 *
	 * @return int
	 */
	public function getTotalRuns(): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "gtr_guardian_run`");

		return (int)$query->row['total'];
	}

	/**
	 * Apply retention to a domain: keep at most $keep_runs newest runs and
	 * none older than $keep_days. Either limit is skipped when 0.
	 *
	 * @param string $domain
	 * @param int    $keep_runs
	 * @param int    $keep_days
	 *
	 * @return void
	 */
	public function prune(string $domain, int $keep_runs, int $keep_days): void {
		$run_ids = [];

		if ($keep_runs > 0) {
			$query = $this->db->query("SELECT `run_id` FROM `" . DB_PREFIX . "gtr_guardian_run` WHERE `domain` = '" . $this->db->escape($domain) . "' ORDER BY `run_id` DESC LIMIT " . (int)$keep_runs . ", 18446744073709551615");

			$run_ids = array_merge($run_ids, array_column($query->rows, 'run_id'));
		}

		if ($keep_days > 0) {
			$query = $this->db->query("SELECT `run_id` FROM `" . DB_PREFIX . "gtr_guardian_run` WHERE `domain` = '" . $this->db->escape($domain) . "' AND `date_started` < DATE_SUB(NOW(), INTERVAL " . (int)$keep_days . " DAY)");

			$run_ids = array_merge($run_ids, array_column($query->rows, 'run_id'));
		}

		$this->deleteRuns(array_unique(array_map('intval', $run_ids)));
	}

	/**
	 * Delete every run and result.
	 *
	 * @return void
	 */
	public function clear(): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "gtr_guardian_result`");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "gtr_guardian_run`");
	}

	/**
	 * @param array<int, int> $run_ids
	 *
	 * @return void
	 */
	private function deleteRuns(array $run_ids): void {
		if (!$run_ids) {
			return;
		}

		$in = implode(',', $run_ids);

		$this->db->query("DELETE FROM `" . DB_PREFIX . "gtr_guardian_result` WHERE `run_id` IN (" . $in . ")");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "gtr_guardian_run` WHERE `run_id` IN (" . $in . ")");
	}
}
