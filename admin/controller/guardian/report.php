<?php
namespace Opencart\Admin\Controller\Extension\GtrGuardian\Guardian;

use Opencart\System\Library\Extension\GtrGuardian\Guardian\CheckResult;
/**
 * Class Report
 *
 * Shared check-results block and run action used by every domain screen.
 * Never dispatched by route: domain controllers call it through
 * $this->load->controller(...) so permissions stay on the domain routes.
 *
 * @package Opencart\Admin\Controller\Extension\GtrGuardian\Guardian
 */
class Report extends \Opencart\System\Engine\Controller {
	/**
	 * Finding keys that identify a store record, mapped to the admin form that
	 * edits it. Such a key is shown as an edit button instead of a column.
	 */
	private const EDIT_ROUTES = ['product_id' => 'catalog/product.form', 'category_id' => 'catalog/category.form', 'filter_group_id' => 'catalog/filter_group.form', 'attribute_id' => 'catalog/attribute.form', 'option_id' => 'catalog/option.form'];

	/**
	 * Finding columns holding a code (or a comma-separated list of codes)
	 * translated through the category's value_<column>_<code> keys.
	 */
	private const CODE_COLUMNS = ['type', 'kind', 'field'];

	private const PRICE_COLUMNS = ['price', 'final_price'];

	private const DATE_COLUMNS = ['date_available', 'date_start', 'date_end'];

	/**
	 * Report order: checks with findings or a failed run first, then the
	 * rest; within each group by severity.
	 */
	private const SEVERITY_ORDER = [CheckResult::SEVERITY_CRITICAL => 0, CheckResult::SEVERITY_WARNING => 1, CheckResult::SEVERITY_INFO => 2];

	/**
	 * Finding columns holding a record id (or a comma-separated list of ids)
	 * shown by name; a deleted record keeps its id.
	 */
	private const NAME_COLUMNS = [
		'customer_group_id' => 'customer_group',
		'store_id'          => 'store'
	];

	/**
	 * Record names per NAME_COLUMNS source, loaded once per request.
	 *
	 * @var array<string, array<int, string>>
	 */
	private array $names = [];

	/**
	 * Render the latest run of a domain as an HTML block.
	 *
	 * @param string $domain
	 *
	 * @return string
	 */
	public function index(string $domain = ''): string {
		if (!$this->isKnownDomain($domain)) {
			return '';
		}

		$this->load->language('extension/gtr_guardian/guardian/report');

		$this->load->model('extension/gtr_guardian/guardian/domain/' . $domain);
		$this->load->model('extension/gtr_guardian/guardian/runner');
		$this->load->model('extension/gtr_guardian/guardian/result');

		$provider = $this->{'model_extension_gtr_guardian_guardian_domain_' . $domain};

		$run = $this->model_extension_gtr_guardian_guardian_result->getLatestRun($domain);

		$data['run'] = [];

		$results = [];

		if ($run) {
			$data['run'] = [
				'date_finished' => date($this->language->get('datetime_format'), strtotime($run['date_finished'])),
				'origin'        => $this->language->get('text_origin_' . $run['origin']),
				'checks_total'  => (int)$run['checks_total'],
				'critical'      => (int)$run['critical'],
				'warning'       => (int)$run['warning'],
				'info'          => (int)$run['info'],
				'errors'        => (int)$run['errors']
			];

			foreach ($this->model_extension_gtr_guardian_guardian_result->getResults((int)$run['run_id']) as $result) {
				$results[$result['category']][$result['code']] = $result;
			}
		}

		// Technical failure detail (may quote SQL) is for Guardian admins only.
		$show_message = $this->user->hasPermission('access', 'extension/gtr_guardian/other/gtr_guardian');

		$data['categories'] = [];

		foreach ($this->model_extension_gtr_guardian_guardian_runner->getCategoryModels($provider->categories()) as $category => $model) {
			$this->load->language('extension/gtr_guardian/guardian/check/' . $category, $category);

			$checks = [];

			foreach ($model->getChecks() as $code => $meta) {
				$result = $results[$category][$code] ?? [];

				$message = '';

				if ($result && $result['status'] === 'error') {
					$message = $show_message ? htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8') : $this->language->get('text_error_hidden');
				}

				$items = $result ? $this->getItems($category, $result['items']) : [];

				// Optional per check: an empty string hides the line.
				$impact_key = $category . '_check_' . $code . '_impact';
				$impact = $this->language->get($impact_key);

				if ($impact === $impact_key) {
					$impact = '';
				}

				$checks[] = [
					'code'     => $code,
					'title'    => $this->language->get($category . '_check_' . $code . '_title'),
					'desc'     => $this->language->get($category . '_check_' . $code . '_desc'),
					'impact'   => $impact,
					'hint'     => $this->language->get($category . '_check_' . $code . '_hint'),
					'severity' => $meta['severity'],
					'status'   => $result ? $result['status'] : 'pending',
					'count'    => $result ? (int)$result['count'] : 0,
					'columns'  => $items ? $this->getColumns($category, $code, array_keys($items[0]['cells'])) : [],
					'items'    => $items,
					'message'  => $message
				];
			}

			// usort is stable, so equal checks keep their registry order.
			$rank = fn(array $check): array => [!in_array($check['status'], [CheckResult::STATUS_FOUND, CheckResult::STATUS_ERROR]), self::SEVERITY_ORDER[$check['severity']] ?? count(self::SEVERITY_ORDER)];

			usort($checks, fn(array $a, array $b): int => $rank($a) <=> $rank($b));

			$data['categories'][] = [
				'code'   => $category,
				'name'   => $this->language->get($category . '_heading_title'),
				'checks' => $checks
			];
		}

		$data['items_limit'] = \Opencart\Admin\Model\Extension\GtrGuardian\Guardian\Check\Base::ITEMS_LIMIT;

		$data['can_run'] = $this->user->hasPermission('modify', 'extension/gtr_guardian/guardian/' . $domain);

		$data['run_url'] = $this->url->link('extension/gtr_guardian/guardian/' . $domain . '.run', 'user_token=' . $this->session->data['user_token']);
		$data['reload_url'] = $this->url->link('extension/gtr_guardian/guardian/' . $domain . '.report', 'user_token=' . $this->session->data['user_token']);

		return $this->load->view('extension/gtr_guardian/guardian/report', $data);
	}

	/**
	 * Run every check of a domain and answer with JSON.
	 *
	 * @param string $domain
	 *
	 * @return void
	 */
	public function run(string $domain = ''): void {
		$this->load->language('extension/gtr_guardian/guardian/report');

		$json = [];

		if (!$this->isKnownDomain($domain) || !$this->user->hasPermission('modify', 'extension/gtr_guardian/guardian/' . $domain)) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('extension/gtr_guardian/guardian/runner');

			$this->model_extension_gtr_guardian_guardian_runner->run($domain);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * @param string $domain
	 *
	 * @return bool
	 */
	private function isKnownDomain(string $domain): bool {
		$this->load->model('extension/gtr_guardian/other/gtr_guardian');

		return $this->model_extension_gtr_guardian_other_gtr_guardian->isKnownDomain($domain);
	}

	/**
	 * The Twig adaptor does not autoescape. Rows come from the store's own
	 * tables, which are only escaped when data entered through the admin
	 * (Request::clean); imports and API writes bypass that. double_encode=false
	 * keeps already-escaped values intact.
	 *
	 * Keys listed in EDIT_ROUTES leave the cells and become the row's edit link.
	 *
	 * @param string                           $category
	 * @param array<int, array<string, mixed>> $items
	 *
	 * @return array<int, array{cells: array<string, string>, edit: string}>
	 */
	private function getItems(string $category, array $items): array {
		$rows = [];

		foreach ($items as $row) {
			$cells = [];
			$edit = '';

			foreach ($row as $key => $value) {
				if (isset(self::EDIT_ROUTES[$key])) {
					$edit = $this->url->link(self::EDIT_ROUTES[$key], 'user_token=' . $this->session->data['user_token'] . '&' . $key . '=' . (int)$value);

					continue;
				}

				// SQL NULL (nothing joined) shows as a dash; an empty string stays empty.
				$cells[htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8', false)] = htmlspecialchars($value === null ? '—' : $this->formatValue($category, (string)$key, (string)$value), ENT_QUOTES, 'UTF-8', false);
			}

			$rows[] = ['cells' => $cells, 'edit' => $edit];
		}

		return $rows;
	}

	/**
	 * Localised headers for the findings sample: a per-check key wins where a
	 * column means something specific to that check, then the category-wide
	 * key, then the raw column name. Language::get() returns the key itself
	 * when it is missing.
	 *
	 * @param string             $category
	 * @param string             $code
	 * @param array<int, string> $keys
	 *
	 * @return array<int, string>
	 */
	private function getColumns(string $category, string $code, array $keys): array {
		$columns = [];

		foreach ($keys as $key) {
			$title = $key;

			foreach ([$category . '_check_' . $code . '_column_' . $key, $category . '_column_' . $key] as $language_key) {
				if ($this->language->get($language_key) !== $language_key) {
					$title = $this->language->get($language_key);

					break;
				}
			}

			$columns[] = $title;
		}

		return $columns;
	}

	/**
	 * Human-readable cell value. A code without a translation is shown as is.
	 *
	 * @param string $category
	 * @param string $key
	 * @param string $value
	 *
	 * @return string
	 */
	private function formatValue(string $category, string $key, string $value): string {
		if ($key == 'status') {
			return $this->language->get((int)$value ? 'text_enabled' : 'text_disabled');
		}

		if (in_array($key, self::CODE_COLUMNS)) {
			$texts = [];

			foreach (explode(', ', $value) as $code) {
				$language_key = $category . '_value_' . $key . '_' . $code;

				$texts[] = $this->language->get($language_key) !== $language_key ? $this->language->get($language_key) : $code;
			}

			return implode(', ', $texts);
		}

		if (isset(self::NAME_COLUMNS[$key])) {
			$names = $this->getNames(self::NAME_COLUMNS[$key]);

			$texts = [];

			foreach (explode(', ', $value) as $id) {
				$texts[] = $names[(int)$id] ?? $id;
			}

			return implode(', ', $texts);
		}

		if (in_array($key, self::PRICE_COLUMNS)) {
			return $this->currency->format((float)$value, $this->config->get('config_currency'));
		}

		if (in_array($key, self::DATE_COLUMNS)) {
			if ($value == '0000-00-00') {
				return '—';
			}

			$time = strtotime($value);

			return $time ? date($this->language->get('date_format_short'), $time) : $value;
		}

		// Decimal columns come back as "0.00000000". Only the trailing zeros
		// go: no thousands separator, so IDs and barcodes stay intact.
		if (is_numeric($value) && str_contains($value, '.')) {
			return str_replace('.', $this->language->get('decimal_point'), rtrim(rtrim($value, '0'), '.'));
		}

		return $value;
	}

	/**
	 * @param string $source "customer_group" or "store"
	 *
	 * @return array<int, string> name by id
	 */
	private function getNames(string $source): array {
		if (!isset($this->names[$source])) {
			$this->names[$source] = [];

			if ($source == 'customer_group') {
				$this->load->model('customer/customer_group');

				foreach ($this->model_customer_customer_group->getCustomerGroups() as $customer_group) {
					$this->names[$source][(int)$customer_group['customer_group_id']] = $customer_group['name'];
				}
			} elseif ($source == 'store') {
				$this->names[$source][0] = $this->config->get('config_name');

				$this->load->model('setting/store');

				foreach ($this->model_setting_store->getStores() as $store) {
					$this->names[$source][(int)$store['store_id']] = $store['name'];
				}
			}
		}

		return $this->names[$source];
	}
}
