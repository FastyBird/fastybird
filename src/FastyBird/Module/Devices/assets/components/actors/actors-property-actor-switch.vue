<template>
	<div
		:data-device-state="isDeviceReady && wsStatus ? 'on' : 'off'"
		class="fb-devices-module-actors-property-actor-switch__container"
	>
		<fb-ui-switch-element
			v-if="command === null"
			:status="value"
			:disabled="!isDeviceReady || !wsStatus"
			:variant="FbUiVariantTypes.PRIMARY"
			:size="FbSizeTypes.MEDIUM"
			@change="onToggleState"
		/>

		<div
			v-show="command === true || command === false"
			class="fb-devices-module-actors-property-actor-switch__result"
		>
			<font-awesome-icon
				v-show="command === false"
				icon="ban"
				class="fb-devices-module-actors-property-actor-switch__result-err"
			/>
			<font-awesome-icon
				v-show="command === true"
				icon="check"
				class="fb-devices-module-actors-property-actor-switch__result-ok"
			/>
		</div>

		<div
			v-show="command !== null && command !== true && command !== false"
			class="fb-devices-module-actors-property-actor-switch__loading"
		>
			<fb-ui-spinner
				:variant="FbUiVariantTypes.PRIMARY"
				:size="FbSizeTypes.MEDIUM"
			/>
		</div>
	</div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import get from 'lodash/get';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { FbUiSwitchElement, FbUiSpinner, FbSizeTypes, FbUiVariantTypes } from '@fastybird/web-ui-library';
import { ActionRoutes, DataType, PropertyAction, SwitchPayload } from '@fastybird/metadata-library';
import { useWsExchangeClient } from '@fastybird/ws-exchange-plugin';

import { useDeviceState, useEntityTitle, useFlashMessage, useNormalizeValue } from '@/composables';
import { IPropertyActorProps } from '@/components/actors/actors-property-actor-switch.types';

const props = defineProps<IPropertyActorProps>();

const emit = defineEmits<{
	(e: 'value', value: string | number | boolean | Date | null): void;
}>();

const { t } = useI18n();
const flashMessage = useFlashMessage();

const wampV1Client = useWsExchangeClient();

const { isReady: isDeviceReady } = props.device ? useDeviceState(props.device) : { isReady: computed<boolean>((): boolean => false) };

const command = ref<boolean | string | null>(null);

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

const { status: wsStatus } = useWsExchangeClient();

let timer: number;

const resetCommand = (): void => {
	command.value = null;

	window.clearTimeout(timer);
};

const onToggleState = async (): Promise<void> => {
	if (props.property === null) {
		return;
	}

	if (command.value !== null) {
		return;
	}

	if (!isDeviceReady.value) {
		flashMessage.error(
			t('messages.notOnline', {
				device: useEntityTitle(props.device).value,
			})
		);

		return;
	}

	let actualValue = false;

	if (props.property.dataType === DataType.BOOLEAN) {
		actualValue = props.property.pending ? !!props.property.expectedValue : !!props.property.actualValue;
	} else if (props.property.dataType === DataType.SWITCH) {
		actualValue = props.property.pending ? props.property.expectedValue === SwitchPayload.ON : props.property.actualValue === SwitchPayload.ON;
	}

	let newValue: boolean | string | null = null;

	if (props.property.dataType === DataType.BOOLEAN) {
		newValue = !actualValue;
	} else if (props.property.dataType === DataType.SWITCH) {
		newValue = actualValue ? SwitchPayload.OFF : SwitchPayload.ON;
	}

	command.value = 'working';

	const backupValue = props.property.actualValue;

	emit('value', useNormalizeValue(props.property.dataType, `${newValue}`, props.property.format));

	try {
		const result = await wampV1Client.call('/io/exchange', {
			routing_key: ActionRoutes.CHANNEL_PROPERTY,
			source: props.property.type.source,
			data: {
				action: PropertyAction.SET,
				device: props.device?.id,
				channel: props.channel?.id,
				property: props.property.id,
				expected_value: props.property.actualValue,
			},
		});

		if (get(result.data, 'response') !== 'accepted') {
			emit('value', backupValue);
		}

		command.value = true;

		timer = window.setTimeout(resetCommand, 500);
	} catch (e) {
		emit('value', backupValue);

		command.value = false;

		flashMessage.error(
			t('messages.commandNotAccepted', {
				device: useEntityTitle(props.device).value,
			})
		);

		timer = window.setTimeout(resetCommand, 500);
	}
};
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'actors-property-actor-switch';
</style>
