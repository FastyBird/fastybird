<template>
	<div
		:data-device-state="isDeviceReady && wsStatus ? 'on' : 'off'"
		:class="[
			{ 'color-success': property.lastResult === PropertyCommandResult.OK },
			{ 'color-error': property.lastResult === PropertyCommandResult.ERR },
		]"
		class="flex flex-col items-center justify-center h-full"
	>
		<el-switch
			v-if="property.command === null"
			v-model="value"
			:disabled="!isDeviceReady || !wsStatus"
			type="primary"
			@change="onToggleState"
		/>

		<el-icon v-show="property.command === PropertyCommandState.COMPLETED && property.lastResult === PropertyCommandResult.ERR">
			<fas-ban />
		</el-icon>

		<el-icon v-show="property.command === PropertyCommandState.COMPLETED && property.lastResult === PropertyCommandResult.OK">
			<fas-check />
		</el-icon>

		<fb-spinner
			v-show="property.command === PropertyCommandState.SENDING"
			type="primary"
			size="small"
		/>
	</div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import get from 'lodash.get';
import { ElIcon, ElSwitch } from 'element-plus';

import { FasCheck, FasBan } from '@fastybird/web-ui-icons';
import { FbSpinner } from '@fastybird/web-ui-library';
import { ActionRoutes, DataType, ModulePrefix, ExchangeCommand, SwitchPayload } from '@fastybird/metadata-library';
import { useWampV1Client } from '@fastybird/vue-wamp-v1';

import { useDeviceState, useEntityTitle, useFlashMessage, useNormalizeValue } from '../../composables';
import { useChannelProperties, useDeviceProperties } from '../../models';
import { IPropertyActorProps } from './actors-property-actor-switch.types';
import { PropertyCommandResult, PropertyCommandState } from '../../models/properties/types';

const props = defineProps<IPropertyActorProps>();

const emit = defineEmits<{
	(e: 'value', value: string | number | boolean | Date | null): void;
}>();

const { t } = useI18n();
const flashMessage = useFlashMessage();

const devicePropertiesStore = useDeviceProperties();
const channelPropertiesStore = useChannelProperties();

const wampV1Client = useWampV1Client();

const { isReady: isDeviceReady } = props.device ? useDeviceState(props.device) : { isReady: computed<boolean>((): boolean => false) };

const value = computed<boolean>((): boolean => {
	if (props.property.dataType === DataType.BOOLEAN) {
		return props.property.expectedValue !== null ? !!props.property.expectedValue : !!props.property.actualValue;
	} else if (props.property.dataType === DataType.SWITCH) {
		return props.property.expectedValue !== null
			? props.property.expectedValue === SwitchPayload.ON
			: props.property.actualValue === SwitchPayload.ON;
	}

	return false;
});

const { status: wsStatus } = useWampV1Client();

let timer: number;

const resetCommand = async (): Promise<void> => {
	if (props.channel !== undefined) {
		await channelPropertiesStore.setState({
			id: props.property.id,
			data: {
				command: null,
				lastResult: null,
			},
		});
	} else {
		await devicePropertiesStore.setState({
			id: props.property.id,
			data: {
				command: null,
				lastResult: null,
			},
		});
	}

	window.clearTimeout(timer);
};

const onToggleState = async (): Promise<void> => {
	if (props.property.command !== null) {
		return;
	}

	if (!isDeviceReady.value) {
		flashMessage.error(
			t('messages.devices.notOnline', {
				device: useEntityTitle(props.device).value,
			})
		);

		return;
	}

	let expectedValue: boolean | string | null = null;

	if (props.property.dataType === DataType.BOOLEAN) {
		expectedValue = !value.value;
	} else if (props.property.dataType === DataType.SWITCH) {
		expectedValue = value.value ? SwitchPayload.OFF : SwitchPayload.ON;
	}

	emit('value', useNormalizeValue(props.property.dataType, `${expectedValue}`, props.property.format, props.property.scale));

	if (props.channel !== undefined) {
		await channelPropertiesStore.setState({
			id: props.property.id,
			data: {
				expectedValue,
				command: PropertyCommandState.SENDING,
				backupValue: props.property.expectedValue,
			},
		});
	} else {
		await devicePropertiesStore.setState({
			id: props.property.id,
			data: {
				expectedValue,
				command: PropertyCommandState.SENDING,
				backupValue: props.property.expectedValue,
			},
		});
	}

	try {
		const result = await wampV1Client.call(`/${ModulePrefix.DEVICES}/v1/exchange`, {
			routing_key: props.channel !== undefined ? ActionRoutes.CHANNEL_PROPERTY : ActionRoutes.DEVICE_PROPERTY,
			source: props.property.type.source,
			data: {
				action: ExchangeCommand.SET,
				device: props.device?.id,
				channel: props.channel?.id,
				property: props.property.id,
				expected_value: props.property.expectedValue,
			},
		});

		if (get(result.data, 'response') !== 'accepted') {
			emit('value', props.property.backupValue);

			if (props.channel !== undefined) {
				await channelPropertiesStore.setState({
					id: props.property.id,
					data: {
						expectedValue: props.property.backupValue,
						command: PropertyCommandState.COMPLETED,
						lastResult: PropertyCommandResult.ERR,
					},
				});
			} else {
				await devicePropertiesStore.setState({
					id: props.property.id,
					data: {
						expectedValue: props.property.backupValue,
						command: PropertyCommandState.COMPLETED,
						lastResult: PropertyCommandResult.ERR,
					},
				});
			}
		} else {
			if (props.channel !== undefined) {
				await channelPropertiesStore.setState({
					id: props.property.id,
					data: {
						command: PropertyCommandState.COMPLETED,
						lastResult: PropertyCommandResult.OK,
					},
				});
			} else {
				await devicePropertiesStore.setState({
					id: props.property.id,
					data: {
						command: PropertyCommandState.COMPLETED,
						lastResult: PropertyCommandResult.OK,
					},
				});
			}
		}

		timer = window.setTimeout(resetCommand, 500);
	} catch (e) {
		emit('value', props.property.backupValue);

		if (props.channel !== undefined) {
			await channelPropertiesStore.setState({
				id: props.property.id,
				data: {
					expectedValue: props.property.backupValue,
					command: PropertyCommandState.COMPLETED,
					lastResult: PropertyCommandResult.ERR,
				},
			});
		} else {
			await devicePropertiesStore.setState({
				id: props.property.id,
				data: {
					expectedValue: props.property.backupValue,
					command: PropertyCommandState.COMPLETED,
					lastResult: PropertyCommandResult.ERR,
				},
			});
		}

		flashMessage.error(
			t('messages.properties.commandNotAccepted', {
				device: useEntityTitle(props.device).value,
			})
		);

		timer = window.setTimeout(resetCommand, 500);
	}
};
</script>
