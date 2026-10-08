<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian;

use Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult;
/**
 * Class Runner
 *
 * Executes every check of a domain, persists the run and applies retention.
 *
 * @package Opencart\Admin\Model\Extension\GtrGuardian\Guardian
 */
class Runner extends \Opencart\System\Engine\Model {
	public const ORIGIN_MANUAL = 'manual';

	/**
	 * Run all checks of a domain.
	 *
	 * A failing check is isolated: it is logged, stored as an error result and
	 * the run continues.
	 *
	 * @param string $domain
	 * @param string $origin
	 *
	 * @return int run_id
	 */
	public function run(string $domain, string $origin = self::ORIGIN_MANUAL): int {
		$this->load->model('extension/gtr_guardian/other/gtr_guardian');

		if (!$this->model_extension_gtr_guardian_other_gtr_guardian->isKnownDomain($domain)) {
			throw new \InvalidArgumentException('Unknown Guardian domain: ' . $domain);
		}

		$this->load->model('extension/gtr_guardian/guardian/domain/' . $domain);
		$this->load->model('extension/gtr_guardian/guardian/result');

		$provider = $this->{'model_extension_gtr_guardian_guardian_domain_' . $domain};
		$store = $this->model_extension_gtr_guardian_guardian_result;

		$models = $this->getCategoryModels($provider->categories());

		$run_id = $store->addRun($domain, $origin, $this->countChecks($models));

		$counts = ['critical' => 0, 'warning' => 0, 'info' => 0, 'errors' => 0];

		foreach ($models as $category => $model) {
			foreach ($model->getChecks() as $code => $meta) {
				try {
					$result = $model->run($code);
				} catch (\Throwable $e) {
					$this->log->write('OpenCart Guardian: check "' . $category . '/' . $code . '" failed - ' . get_class($e) . ': ' . $e->getMessage());

					$result = CheckResult::error($code, $meta['severity'], get_class($e) . ': ' . $e->getMessage());
				}

				if ($result->status === CheckResult::STATUS_FOUND) {
					$counts[$result->severity] = ($counts[$result->severity] ?? 0) + 1;
				} elseif ($result->status === CheckResult::STATUS_ERROR) {
					$counts['errors']++;
				}

				$store->addResult($run_id, $category, $result);
			}
		}

		$store->finishRun($run_id, $counts);

		$retention = $this->model_extension_gtr_guardian_other_gtr_guardian->getRetention();

		$store->prune($domain, $retention['runs'], $retention['days']);

		return $run_id;
	}

	/**
	 * Load the check models behind a list of category codes.
	 *
	 * A category without a model file yet is skipped, so a domain may declare
	 * its full category list ahead of the implementation.
	 *
	 * @param array<int, string> $categories
	 *
	 * @return array<string, \Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check\Base>
	 */
	public function getCategoryModels(array $categories): array {
		$models = [];

		foreach ($categories as $category) {
			if (!is_file(DIR_EXTENSION . 'gtr_guardian/admin/model/guardian/check/' . $category . '.php')) {
				continue;
			}

			$this->load->model('extension/gtr_guardian/guardian/check/' . $category);

			$models[$category] = $this->{'model_extension_gtr_guardian_guardian_check_' . $category};
		}

		return $models;
	}

	/**
	 * Number of checks shipped for a list of categories.
	 *
	 * @param array<int, string> $categories
	 *
	 * @return int
	 */
	public function getChecksTotal(array $categories): int {
		return $this->countChecks($this->getCategoryModels($categories));
	}

	/**
	 * @param array<string, \Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check\Base> $models
	 *
	 * @return int
	 */
	private function countChecks(array $models): int {
		$total = 0;

		foreach ($models as $model) {
			$total += count($model->getChecks());
		}

		return $total;
	}
}
