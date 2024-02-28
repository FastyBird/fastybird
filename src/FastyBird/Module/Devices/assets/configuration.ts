import { InjectionKey } from 'vue';
import { IDevicesModuleConfiguration, IDevicesModuleMeta } from './types';

export const metaKey: InjectionKey<IDevicesModuleMeta> = Symbol('devices-module_meta');
export const configurationKey: InjectionKey<IDevicesModuleConfiguration> = Symbol('devices-module_configuration');
