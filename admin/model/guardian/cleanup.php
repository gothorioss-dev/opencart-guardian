<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian;

/**
 * Class Cleanup
 *
 * Audit journal of data cleanups: one row in gtr_guardian_cleanup per
 * cleanup run by an administrator. It is not touched by the run history
 * actions ("clear history"), only by its own retention and uninstall.
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian
 */
class Cleanup extends \Opencart\System\Engine\Model {
	/**
	 * @return void
	 */
	public function createTables(): void {
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "gtr_guardian_cleanup` (
		  `cleanup_id` int(11) NOT NULL AUTO_INCREMENT,
		  `user_id` int(11) NOT NULL,
		  `domain` varchar(32) NOT NULL,
		  `category` varchar(32) NOT NULL,
		  `code` varchar(64) NOT NULL,
		  `expected` int(11) NOT NULL DEFAULT '0',
		  `deleted` int(11) NOT NULL DEFAULT '0',
		  `date_added` datetime NOT NULL,
		  PRIMARY KEY (`cleanup_id`),
		  KEY `date_added` (`date_added`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
	}

	/**
	 * @return void
	 */
	public function dropTables(): void {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "gtr_guardian_cleanup`");
	}

	/**
	 * Record a finished cleanup for the current admin user.
	 *
	 * @param string $domain
	 * @param string $category
	 * @param string $code
	 * @param int    $expected count shown to the user before the cleanup
	 * @param int    $deleted  rows actually deleted
	 *
	 * @return int cleanup_id
	 */
	public function addCleanup(string $domain, string $category, string $code, int $expected, int $deleted): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "gtr_guardian_cleanup` SET `user_id` = '" . (int)$this->user->getId() . "', `domain` = '" . $this->db->escape($domain) . "', `category` = '" . $this->db->escape($category) . "', `code` = '" . $this->db->escape($code) . "', `expected` = '" . (int)$expected . "', `deleted` = '" . (int)$deleted . "', `date_added` = NOW()");

		return $this->db->getLastId();
	}

	/**
	 * Journal retention: delete entries older than $keep_days (0 = keep all).
	 *
	 * @param int $keep_days
	 *
	 * @return void
	 */
	public function prune(int $keep_days): void {
		if ($keep_days > 0) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "gtr_guardian_cleanup` WHERE `date_added` < DATE_SUB(NOW(), INTERVAL " . (int)$keep_days . " DAY)");
		}
	}

	/**
	 * Journal page, newest first; `username` is NULL when the user has been
	 * deleted since.
	 *
	 * @param int $start
	 * @param int $limit
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getCleanups(int $start = 0, int $limit = 20): array {
		$query = $this->db->query("SELECT `c`.*, `u`.`username` FROM `" . DB_PREFIX . "gtr_guardian_cleanup` `c` LEFT JOIN `" . DB_PREFIX . "user` `u` ON (`u`.`user_id` = `c`.`user_id`) ORDER BY `c`.`cleanup_id` DESC LIMIT " . (int)max(0, $start) . ", " . (int)max(1, $limit));

		return $query->rows;
	}

	/**
	 * @return int
	 */
	public function getTotalCleanups(): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "gtr_guardian_cleanup`");

		return (int)$query->row['total'];
	}
}
