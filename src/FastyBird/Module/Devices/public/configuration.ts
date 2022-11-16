import { InjectionKey } from 'vue';
import { IDeviceModuleConfiguration, IDeviceModuleMeta } from '@/types';

export const metaKey: InjectionKey<IDeviceModuleMeta> = Symbol('devices-module_meta');
export const configurationKey: InjectionKey<IDeviceModuleConfiguration> = Symbol('devices-module_configuration');
