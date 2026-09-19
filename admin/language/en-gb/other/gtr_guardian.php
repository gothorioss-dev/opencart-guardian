<?php
// Heading
$_['heading_title']               = 'OpenCart Guardian';

// Text
$_['text_home']                   = 'Home';
$_['text_extension']              = 'Extensions';
$_['text_guardian']               = 'OpenCart Guardian';
$_['text_dashboard']              = 'Dashboard';
$_['text_settings']               = 'Settings';
$_['text_edit']                   = 'Edit OpenCart Guardian';
$_['text_success']                = 'Success: You have modified OpenCart Guardian!';
$_['text_enabled']                = 'Enabled';
$_['text_disabled']               = 'Disabled';
$_['text_domains']                = 'Diagnostic domains';
$_['text_domains_help']           = 'Turn a domain off to hide it from the menu and skip it on the dashboard. A domain added by an update is enabled by default.';
$_['text_history']                = 'Run history';
$_['text_history_help']           = 'Every run of a domain is stored so results can be compared over time. Old runs are deleted automatically after each run according to the limits below.';
$_['text_retention_off']          = 'Off';
$_['text_retention_custom']       = 'Custom…';
$_['text_runs']                   = 'runs';
$_['text_days']                   = 'days';
$_['text_retention_current_both'] = 'Currently: the newest %d runs per domain are kept, none older than %d days — whichever limit is reached first.';
$_['text_retention_current_runs'] = 'Currently: the newest %d runs per domain are kept; there is no age limit.';
$_['text_retention_current_days'] = 'Currently: runs older than %d days are deleted; there is no count limit.';
$_['text_retention_current_none'] = 'Currently: automatic clean-up is off — history grows until it is cleared manually.';
$_['text_history_total']          = 'Stored runs: %d';
$_['text_clear_confirm']          = 'Delete the whole run history of every domain?';
$_['text_clear_success']          = 'Success: run history has been cleared!';
$_['text_permission_help']        = 'Which user groups may open (A — access) and act on (M — modify: run checks) each Guardian screen. A group with no access to a domain does not see it in the menu or on the dashboard.';
$_['text_permission_admin']       = 'Groups allowed to modify these settings are not listed: they administer Guardian and cannot restrict themselves here. Their access is managed in System &gt; Users &gt; User Groups.';
$_['text_access']                 = 'Access';
$_['text_modify']                 = 'Modify';

// Tab
$_['tab_general']                 = 'General';
$_['tab_config']                  = 'Configuration';
$_['tab_permission']              = 'Permissions';

// Entry
$_['entry_status']                = 'Status';
$_['entry_retention_runs']        = 'Keep runs';
$_['entry_retention_days']        = 'Keep for';

// Help
$_['help_status']                 = 'Disabled: the Guardian menu and all its screens are hidden; stored results are kept.';
$_['help_retention_runs']         = 'Newest runs kept per domain. Off — no limit by count.';
$_['help_retention_days']         = 'Maximum age of a run in days. Off — no limit by age.';

// Column
$_['column_group']                = 'User group';
$_['column_domain']               = 'Domain';
$_['column_enabled']              = 'Enabled';
$_['column_action']               = 'Action';

// Button
$_['button_save']                 = 'Save';
$_['button_back']                 = 'Back';
$_['button_edit']                 = 'Open';
$_['button_clear']                = 'Clear history';

// Error
$_['error_permission']            = 'Warning: You do not have permission to modify OpenCart Guardian!';
