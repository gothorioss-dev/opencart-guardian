<?php
namespace Opencart\Admin\Model\Extension\GtrGuardian\Guardian;

use Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult;
use Opencart\System\Library\Extension\GtrGuardian\Guardian\SubmoduleReport;
/**
 * Class Runner
 *
 * Executes every check of a domain, persists the run, applies retention and
 * refreshes the per-domain summary setting read by the dashboard and menu.
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
		$this->load->model('extension/gtr_guardian/guardian/domain/' . $domain);
		$this->load->model('extension/gtr_guardian/guardian/result');
		$this->load->model('extension/gtr_guardian/other/gtr_guardian');

		$provider = $this->{'model_extension_gtr_guardian_guardian_domain_' . $domain};
		$store = $this->model_extension_gtr_guardian_guardian_result;

		$models = $this->getCategoryModels($provider->categories());

		$total = 0;

		foreach ($models as $model) {
			$total += count($model->getChecks());
		}

		$run_id = $store->addRun($domain, $origin, $total);

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

		$this->updateSummary($domain, $store->getLatestRun($domain));

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
		$total = 0;

		foreach ($this->getCategoryModels($categories) as $model) {
			$total += count($model->getChecks());
		}

		return $total;
	}

	/**
	 * Drop the summary of every domain (after the history was cleared).
	 *
	 * @return void
	 */
	public function clearSummary(): void {
		$this->saveSummary([]);
	}

	/**
	 * Refresh one domain's entry in the gtr_guardian_summary setting.
	 *
	 * @param string               $domain
	 * @param array<string, mixed> $run latest finished run row
	 *
	 * @return void
	 */
	private function updateSummary(string $domain, array $run): void {
		$summary = $this->config->get('gtr_guardian_summary');
		$summary = is_array($summary) ? $summary : [];

		if ($run) {
			$counts = [
				'critical' => (int)$run['critical'],
				'warning'  => (int)$run['warning'],
				'info'     => (int)$run['info'],
				'errors'   => (int)$run['errors']
			];

			if ($counts['critical']) {
				$status = SubmoduleReport::STATUS_CRITICAL;
			} elseif ($counts['warning']) {
				$status = SubmoduleReport::STATUS_WARNING;
			} elseif ($counts['errors'] && !$counts['info']) {
				$status = SubmoduleReport::STATUS_ERROR;
			} else {
				$status = SubmoduleReport::STATUS_OK;
			}

			$summary[$domain] = [
				'run_id'       => (int)$run['run_id'],
				'status'       => $status,
				'counts'       => $counts,
				'last_run'     => strtotime($run['date_finished']),
				'checks_total' => (int)$run['checks_total']
			];
		} else {
			unset($summary[$domain]);
		}

		$this->saveSummary($summary);
	}

	/**
	 * @param array<string, array<string, mixed>> $summary
	 *
	 * @return void
	 */
	private function saveSummary(array $summary): void {
		$this->load->model('setting/setting');

		$settings = $this->model_setting_setting->getSetting('gtr_guardian');

		$settings['gtr_guardian_summary'] = $summary;

		$this->model_setting_setting->editSetting('gtr_guardian', $settings);

		// Keep the in-request view consistent with what was just written.
		$this->config->set('gtr_guardian_summary', $summary);
	}
}
