<template>
	<div class="fb-accounts-module-sign-box__container">
		<div class="fb-accounts-module-sign-box__container-inner">
			<fb-layout-sign-box>
				<div class="fb-accounts-module-sign-box__box">
					<router-link
						:to="{ name: routeNames.signIn }"
						class="fb-accounts-module-sign-box__logo"
					>
						<logo />
					</router-link>

					<slot />
				</div>
			</fb-layout-sign-box>

			<fb-layout-sign-footer>
				<template #info>
					<p v-if="route.name === routeNames.signUp">
						Already have an account?
						<router-link :to="{ name: routeNames.signIn }"> Sign in </router-link>
					</p>

					<p v-else>
						Don't have an account?
						<router-link :to="{ name: routeNames.signUp }"> Sign up </router-link>
					</p>
				</template>

				<template #links>
					<fb-layout-sign-footer-item>
						<a href="#">Privacy Policy</a>
					</fb-layout-sign-footer-item>
					<fb-layout-sign-footer-item>
						<a href="#">Terms</a>
					</fb-layout-sign-footer-item>
					<fb-layout-sign-footer-item>
						<a href="#">Cookie Policy</a>
					</fb-layout-sign-footer-item>
					<fb-layout-sign-footer-item
						v-if="moduleMeta"
						class="fb-accounts-module-sign-box__owner"
					>
						&copy;
						<a
							:href="moduleMeta?.website"
							target="_blank"
						>
							{{ moduleMeta?.author }}
						</a>
						2017
					</fb-layout-sign-footer-item>
				</template>
			</fb-layout-sign-footer>
		</div>
	</div>
</template>

<script setup lang="ts">
import { inject } from 'vue';
import { useRoute } from 'vue-router';

import { FbLayoutSignBox, FbLayoutSignFooter, FbLayoutSignFooterItem } from '@fastybird/web-ui-library';

import { metaKey } from '../../configuration';
import { useRoutesNames } from '../../composables';

// @ts-ignore
import Logo from '../../assets/images/fastybird_bird.svg?component';

const { routeNames } = useRoutesNames();

const moduleMeta = inject(metaKey);

const route = useRoute();
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'sign-box';
</style>
