<template>
	<div class="flex flex-row items-center align-center w-full h-full">
		<div class="mx-a w-[25rem]">
			<el-card
				v-if="!isXsBreakpoint"
				class="mb-5"
			>
				<router-link
					:to="{ name: routeNames.signIn }"
					class="block w-[8rem] my-0 mx-a"
				>
					<logo class="fill-brand-primary" />
				</router-link>

				<slot />
			</el-card>

			<div
				v-else
				class="mb-5"
			>
				<router-link
					:to="{ name: routeNames.signIn }"
					class="block w-[8rem] my-0 mx-a"
				>
					<logo class="fill-brand-primary" />
				</router-link>

				<slot />
			</div>

			<div class="text-center">
				<i18n-t
					v-if="route.name === routeNames.signUp"
					keypath="messages.haveAccount"
					tag="p"
				>
					<router-link :to="{ name: routeNames.signIn }"> {{ t('buttons.signIn.title') }} </router-link>
				</i18n-t>

				<i18n-t
					v-else
					keypath="messages.notHaveAccount"
					tag="p"
				>
					<router-link :to="{ name: routeNames.signUp }"> {{ t('buttons.signUp.title') }} </router-link>
				</i18n-t>

				<div class="flex flex-row justify-center items-center mb-5">
					<el-link
						:underline="false"
						href="http://www.github.com/fastybird"
						target="_blank"
						class="mx-5"
					>
						<template #icon>
							<el-icon :size="20">
								<fab-github />
							</el-icon>
						</template>
					</el-link>

					<el-divider direction="vertical" />

					<el-link
						:underline="false"
						href="http://www.x.com/fastybird"
						target="_blank"
						class="mx-5"
					>
						<template #icon>
							<el-icon :size="20">
								<fab-x-twitter />
							</el-icon>
						</template>
					</el-link>

					<el-divider direction="vertical" />

					<el-link
						:underline="false"
						href="http://www.facebook.com/fastybird"
						target="_blank"
						class="mx-5"
					>
						<template #icon>
							<el-icon :size="20">
								<fab-facebook />
							</el-icon>
						</template>
					</el-link>
				</div>

				<div class="flex flex-row justify-center items-baseline gap-[0.5rem]">
					&copy;
					<el-link
						:href="moduleMeta?.website"
						target="_blank"
					>
						{{ moduleMeta?.author }}
					</el-link>
					2017
				</div>
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
import { inject } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { ElCard, ElDivider, ElLink, ElIcon } from 'element-plus';

import { breakpointsBootstrapV5, useBreakpoints } from '@vueuse/core';
import { FabXTwitter, FabFacebook, FabGithub } from '@fastybird/web-ui-icons';

import { metaKey } from '../../configuration';
import { useRoutesNames } from '../../composables';

// @ts-ignore
import Logo from '../../assets/images/fastybird_bird.svg?component';

defineOptions({
	name: 'SignBox',
});

const { routeNames } = useRoutesNames();
const { t } = useI18n();

const moduleMeta = inject(metaKey);

const route = useRoute();
const breakpoints = useBreakpoints(breakpointsBootstrapV5);

const isXsBreakpoint = breakpoints.smaller('sm');
</script>
