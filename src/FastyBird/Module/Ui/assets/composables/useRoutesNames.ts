import { IRoutes } from '../types';

export function useRoutesNames(): { routeNames: IRoutes } {
	const routeNames: IRoutes = {
		root: 'ui_module-root',

		dashboards: 'ui_module-dashboards',
		dashboardDetail: 'ui_module-dashboard_detail',
		dashboardSettings: 'ui_module-dashboard_settings',
		dashboardSettingsAddWidget: 'ui_module-dashboard_settings_add_widget',
		dashboardSettingsEditWidget: 'ui_module-dashboard_settings_edit_widget',

		groups: 'ui_module-groups',
		groupDetail: 'ui_module-group_detail',
		groupSettings: 'ui_module-group_settings',
		groupSettingsAddWidget: 'ui_module-group_settings_add_widget',
		groupSettingsEditWidget: 'ui_module-group_settings_edit_widget',

		widgets: 'ui_module-widgets',
		widgetDetail: 'ui_module-widget_detail',
		widgetSettings: 'ui_module-widget_settings',
	};

	return {
		routeNames,
	};
}
