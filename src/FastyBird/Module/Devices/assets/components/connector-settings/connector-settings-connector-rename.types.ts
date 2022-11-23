export interface IConnectorSettingsConnectorRenameModel {
	identifier: string;
	name?: string;
	comment?: string;
}

export interface IConnectorSettingsConnectorRenameProps {
	modelValue: IConnectorSettingsConnectorRenameModel;
	errors: {
		identifier?: string;
		name?: string;
		comment?: string;
	};
}
