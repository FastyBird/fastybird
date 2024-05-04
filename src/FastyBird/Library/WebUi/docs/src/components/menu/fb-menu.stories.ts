import { Meta, StoryObj } from "@storybook/vue3";
import { FbMenu, FbMenuItem, FbMenuItemGroup, FbSubMenu, FbRow, FbCol, FbIcon, FbRadioGroup, FbRadioButton } from "@fastybird/web-ui-components";
import { FasLocationDot, FasBars, FarFileLines, FasGear } from "@fastybird/web-ui-icons";

import "./fb-menu.stories.scss";
import { ref } from "vue";

const meta: Meta<typeof FbMenu> = {
    component: FbMenu,
    title: "Components/Navigation/Menu",
    excludeStories: /.*Data$/,
};

export default meta;

type Story = StoryObj<typeof FbMenu>;

export const TopBar: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-menu
		:default-active="activeIndex1"
		mode="horizontal"
		@select="handleSelect"
	>
		<fb-menu-item index="1">Processing Center</fb-menu-item>
		<fb-sub-menu index="2">
			<template #title>Workspace</template>
			<fb-menu-item index="2-1">item one</fb-menu-item>
			<fb-menu-item index="2-2">item two</fb-menu-item>
			<fb-menu-item index="2-3">item three</fb-menu-item>
			<fb-sub-menu index="2-4">
				<template #title>item four</template>
				<fb-menu-item index="2-4-1">item one</fb-menu-item>
				<fb-menu-item index="2-4-2">item two</fb-menu-item>
				<fb-menu-item index="2-4-3">item three</fb-menu-item>
			</fb-sub-menu>
		</fb-sub-menu>
		<fb-menu-item index="3" disabled>Info</fb-menu-item>
		<fb-menu-item index="4">Orders</fb-menu-item>
	</fb-menu>

	<fb-menu
		:default-active="activeIndex2"
		mode="horizontal"
		background-color="#545c64"
		text-color="#fff"
		active-text-color="#ffd04b"
		@select="handleSelect"
	>
		<fb-menu-item index="1">Processing Center</fb-menu-item>
		<fb-sub-menu index="2">
			<template #title>Workspace</template>
			<fb-menu-item index="2-1">item one</fb-menu-item>
			<fb-menu-item index="2-2">item two</fb-menu-item>
			<fb-menu-item index="2-3">item three</fb-menu-item>
			<fb-sub-menu index="2-4">
				<template #title>item four</template>
				<fb-menu-item index="2-4-1">item one</fb-menu-item>
				<fb-menu-item index="2-4-2">item two</fb-menu-item>
				<fb-menu-item index="2-4-3">item three</fb-menu-item>
			</fb-sub-menu>
		</fb-sub-menu>
		<fb-menu-item index="3" disabled>Info</fb-menu-item>
		<fb-menu-item index="4">Orders</fb-menu-item>
	</fb-menu>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const activeIndex1 = ref('1');
const activeIndex2 = ref('1');

const handleSelect = (key: string, keyPath: string[]): void => {
	console.log(key, keyPath);
}
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbMenu, FbSubMenu, FbMenuItem },
        setup: () => {
            const activeIndex1 = ref("1");
            const activeIndex2 = ref("1");

            const handleSelect = (key: string, keyPath: string[]): void => {
                console.log(key, keyPath);
            };

            return {
                activeIndex1,
                activeIndex2,
                handleSelect,
            };
        },
        template: `
<div class="fb-menu-story-block">
	<div class="fb-menu-story-block__item">
		<fb-menu
			:default-active="activeIndex1"
			mode="horizontal"
			@select="handleSelect"
		>
			<fb-menu-item index="1">Processing Center</fb-menu-item>
			<fb-sub-menu index="2">
				<template #title>Workspace</template>
				<fb-menu-item index="2-1">item one</fb-menu-item>
				<fb-menu-item index="2-2">item two</fb-menu-item>
				<fb-menu-item index="2-3">item three</fb-menu-item>
				<fb-sub-menu index="2-4">
					<template #title>item four</template>
					<fb-menu-item index="2-4-1">item one</fb-menu-item>
					<fb-menu-item index="2-4-2">item two</fb-menu-item>
					<fb-menu-item index="2-4-3">item three</fb-menu-item>
				</fb-sub-menu>
			</fb-sub-menu>
			<fb-menu-item index="3" disabled>Info</fb-menu-item>
			<fb-menu-item index="4">Orders</fb-menu-item>
		</fb-menu>
	</div>

	<div class="fb-menu-story-block__item">
		<fb-menu
			:default-active="activeIndex2"
			mode="horizontal"
			background-color="#545c64"
			text-color="#fff"
			active-text-color="#ffd04b"
			@select="handleSelect"
		>
			<fb-menu-item index="1">Processing Center</fb-menu-item>
			<fb-sub-menu index="2">
				<template #title>Workspace</template>
				<fb-menu-item index="2-1">item one</fb-menu-item>
				<fb-menu-item index="2-2">item two</fb-menu-item>
				<fb-menu-item index="2-3">item three</fb-menu-item>
				<fb-sub-menu index="2-4">
					<template #title>item four</template>
					<fb-menu-item index="2-4-1">item one</fb-menu-item>
					<fb-menu-item index="2-4-2">item two</fb-menu-item>
					<fb-menu-item index="2-4-3">item three</fb-menu-item>
				</fb-sub-menu>
			</fb-sub-menu>
			<fb-menu-item index="3" disabled>Info</fb-menu-item>
			<fb-menu-item index="4">Orders</fb-menu-item>
		</fb-menu>
	</div>
</div>`,
    }),
};

export const LeftRight: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-menu
		:default-active="activeIndex"
		mode="horizontal"
		:ellipsis="false"
		@select="handleSelect"
	>
		<fb-menu-item index="0">
			<svg
				version="1.1"
				xmlns="http://www.w3.org/2000/svg"
				viewBox="0 0 46.4098 10.4641"
				width="150"
				alt="FastyBird logo"
			>
				<path
					style="fill: #d9230f"
					d="M0.677699 6.13129c-0.374299,0 -0.677699,-0.303399 -0.677699,-0.677599 0,-0.374199 0.303399,-0.677699 0.677699,-0.677699 0.947098,0 1.8859,-0.0106 2.83409,-0.00759998 0.1466,-0.965398 0.601499,-1.8298 1.261,-2.48919 0.729298,-0.729398 1.7093,-1.2085 2.79949,-1.297 -0.1232,-0.241299 -0.0932998,-0.543399 0.0979998,-0.757298 0.248499,-0.277699 0.675299,-0.301399 0.952998,-0.0528999l1.5326 1.3707 0.0282999 0.0269999c0.337999,0.1961 0.647799,0.435099 0.922298,0.709498 0.292599,0.292499 0.544999,0.625499 0.747898,0.989698l1.5411 1.432c0.1491,0.1217 0.273399,0.322999 0.325999,0.499299l-1.6534 0c-0.1927,0 -0.351299,0.1585 -0.351299,0.351199l0 0.000299999c0,0.1926 0.1587,0.351299 0.351299,0.351299 0.537099,0 1.0741,0 1.6112,0 -0.0531999,0.1225 -0.1485,0.265899 -0.250599,0.352999l-1.5888 1.4764c-0.0291999,0.0270999 -0.0598999,0.0508999 -0.0921998,0.0719998 -0.1824,0.293899 -0.397799,0.565099 -0.641199,0.808498 -0.810298,0.810498 -1.9301,1.312 -3.16689,1.312 -1.157,0 -2.2955,-0.617199 -3.35279,-1.412l1.4935 0c0.657399,0 1.1908,-0.533299 1.1908,-1.1908 0,-0.657399 -0.533399,-1.1908 -1.1908,-1.1908 -1.8009,0 -3.60179,0 -5.40259,0zm8.31068 2.2577c0.440599,-0.157 0.835798,-0.410199 1.1599,-0.734298 0.565199,-0.564999 0.914798,-1.3461 0.914798,-2.2086 0,-0.862498 -0.349599,-1.6436 -0.914798,-2.2087 -0.565099,-0.565099 -1.3461,-0.914798 -2.2087,-0.914798 -0.862498,0 -1.6436,0.349699 -2.2086,0.914798 -0.413599,0.413699 -0.711798,0.943098 -0.841898,1.5354 0.471799,0.0014 0.943098,0.00319999 1.4149,0.00319999 1.2892,0 2.2028,0.739598 2.61599,1.6303 0.285199,0.615399 0.341499,1.3531 0.0683999,1.9827zm1.2565 -3.68889c0,-0.410899 -0.333099,-0.744098 -0.744098,-0.744098 -0.410899,0 -0.743998,0.333199 -0.743998,0.744098 0,0.410899 0.333099,0.744098 0.743998,0.744098 0.410999,0 0.744098,-0.333199 0.744098,-0.744098zm-4.16459 1.9444c0.374199,0 0.677599,0.303399 0.677599,0.677599 0,0.374199 -0.303399,0.677699 -0.677599,0.677699l-3.68299 0c-0.374199,0 -0.677699,-0.303499 -0.677699,-0.677699 0,-0.374199 0.303499,-0.677599 0.677699,-0.677599l3.68299 0z"
				/>
				<path
					style="fill: #474949"
					d="M19.2432 5.92919l-1.4008 0 -0.759798 2.92829 -1.1792 0 1.2504 -4.79599c-0.1794,0.1055 -0.390399,0.1583 -0.633099,0.1583 -0.0791998,0 -0.1609,-0.00529999 -0.245399,-0.0158l0.1979 -0.759798c0.0685999,0.0105 0.1345,0.0158 0.1979,0.0158 0.342899,0 0.604099,-0.1794 0.783498,-0.538199l3.10229 0 -0.237399 0.815198 -1.9073 0 -0.356199 1.3692 1.4246 0 -0.237399 0.822998zm0.773198 2.92039c-0.427299,-0.1847 -0.640999,-0.609399 -0.640999,-1.2742 0,-0.369299 0.0790998,-0.738698 0.237399,-1.108 0.1741,-0.411499 0.411599,-0.747898 0.712298,-1.009 0.300699,-0.261199 0.601499,-0.391799 0.902198,-0.391799 0.295499,0 0.530299,0.1213 0.704398,0.364099l0.0869998 -0.324499 1.0684 0 -0.688499 2.65909c-0.0105,0.0369999 -0.0158,0.0712998 -0.0158,0.1029 0,0.1055 0.0606999,0.1583 0.182,0.1583 0.0685999,0 0.1451,-0.0158 0.2295,-0.0474999l-0.1978 0.696498c-0.274399,0.1846 -0.527599,0.276999 -0.759798,0.276999 -0.263799,0 -0.485399,-0.1187 -0.664799,-0.356199 -0.274299,0.2111 -0.546099,0.316599 -0.815198,0.316599 -0.1213,0 -0.234699,-0.0211 -0.340299,-0.0632999zm1.3217 -2.93619c-0.1266,0 -0.262499,0.0791998 -0.407599,0.237399 -0.1451,0.1583 -0.265099,0.366699 -0.360099,0.625299 -0.0896998,0.237399 -0.1345,0.466899 -0.1345,0.688499 0,0.353499 0.0922998,0.530299 0.276999,0.530299 0.1213,0 0.255899,-0.0844998 0.403599,-0.253299 0.1477,-0.1688 0.269099,-0.385199 0.364099,-0.648999 0.0948998,-0.237399 0.1424,-0.461599 0.1424,-0.672699 0,-0.337599 -0.0949998,-0.506499 -0.284899,-0.506499zm4.86489 0.261199c-0.300699,-0.2163 -0.654199,-0.324499 -1.0605,-0.324499 -0.0896998,0 -0.1794,0.0289999 -0.269099,0.0870998 -0.0896998,0.0579999 -0.1345,0.1292 -0.1345,0.2136 0,0.0844998 0.1134,0.1847 0.340299,0.300799 0.316599,0.1583 0.527599,0.279599 0.633099,0.363999 0.2216,0.1847 0.332399,0.406299 0.332399,0.664799 0,0.448499 -0.1503,0.808598 -0.451099,1.0803 -0.300699,0.271699 -0.677999,0.407599 -1.1317,0.407599 -0.153,0 -0.369299,-0.0316999 -0.648999,-0.0949998 -0.305999,-0.0632999 -0.503799,-0.1292 -0.593499,-0.1978l0.2216 -0.878498c0.290199,0.1688 0.638399,0.253299 1.0446,0.253299 0.348299,0 0.522399,-0.0976998 0.522399,-0.292899 0,-0.1477 -0.1042,-0.279599 -0.312599,-0.395699 -0.2084,-0.1161 -0.415499,-0.2295 -0.621299,-0.340299 -0.2163,-0.1636 -0.324499,-0.369299 -0.324499,-0.617299 0,-0.406299 0.1583,-0.746598 0.474899,-1.0209 0.300699,-0.258599 0.656899,-0.387799 1.0684,-0.387799 0.469599,0 0.846798,0.0975998 1.1317,0.292799l-0.2216 0.886398zm1.3985 2.68289l-1.0922 0 0.759798 -2.92829 -0.498599 0 0.2137 -0.815098 0.498599 0 0.427399 -1.6224 1.0921 0 -0.419399 1.6224 0.688499 0 -0.2216 0.815098 -0.680599 0 -0.767698 2.92829zm3.32959 0c-0.2268,0.369299 -0.379799,0.606799 -0.458999,0.712298 -0.1635,0.2163 -0.363999,0.406199 -0.601499,0.569799 -0.311299,0.2163 -0.614599,0.324499 -0.910098,0.324499 -0.448499,-0.0211 -0.670099,-0.1636 -0.664799,-0.427399l0.514399 -0.712298c0.00529999,0.1056 0.0817998,0.1557 0.2295,0.1504 0.247999,-0.0211 0.514499,-0.2269 0.799398,-0.617299l-0.364099 -3.75129 1.0922 0 0.2137 2.2001 1.3454 -2.1922 1.0921 0 -2.2872 3.74339zm4.02599 -2.69079l0.1742 -0.720198c0.0473999,0.00529999 0.0790998,0.00789998 0.0948998,0.00789998 0.1003,0 0.1583,0 0.1741,0 0.517099,-0.0368999 0.828398,-0.324499 0.933898,-0.862698 0.0106,-0.0526999 0.0158,-0.1002 0.0158,-0.1424 0,-0.284899 -0.1134,-0.490699 -0.340299,-0.617299 -0.1635,-0.0949998 -0.334999,-0.1319 -0.514399,-0.1108 -0.0632999,0.0158 -0.1081,0.0211 -0.1345,0.0158l-1.3375 5.12049 -1.2584 0 1.2505 -4.79599c-0.1794,0.1055 -0.390499,0.1583 -0.633199,0.1583 -0.0790998,0 -0.1609,-0.00529999 -0.245299,-0.0158l0.1978 -0.759798c0.0685999,0.0105 0.1346,0.0158 0.1979,0.0158 0.342899,0 0.604099,-0.1794 0.783498,-0.538199l1.0209 -0.00789998c0.886398,-0.0105 1.5011,0.2243 1.844,0.704398 0.1742,0.2216 0.261199,0.477499 0.261199,0.767698 0,0.274299 -0.0738998,0.539499 -0.2216,0.795398 -0.1477,0.255799 -0.342899,0.457699 -0.585599,0.605399 0.342899,0.284899 0.514399,0.672699 0.514399,1.1634 0,0.295399 -0.0711998,0.593499 -0.2137,0.894298 -0.1688,0.348199 -0.394399,0.617299 -0.676699,0.807198 -0.282199,0.19 -0.576399,0.284899 -0.882398,0.284899 -0.2163,0 -0.422099,-0.0473999 -0.617299,-0.1424 -0.0579999,-0.0263999 -0.2031,-0.1003 -0.435299,-0.2216l0.2137 -0.870598c0.0421999,0.0474999 0.0896998,0.0896998 0.1425,0.1267 0.1213,0.0895998 0.250599,0.1398 0.387699,0.1503 0.0422999,0.0159 0.0949998,0.0237999 0.1583,0.0237999 0.1689,0 0.335099,-0.0606999 0.498599,-0.1821 0.19,-0.1477 0.308699,-0.340299 0.356199,-0.577699 0.0158,-0.0896998 0.0236999,-0.1715 0.0236999,-0.245299 0,-0.342999 -0.1662,-0.580399 -0.498599,-0.712298 -0.1847,-0.0738998 -0.400999,-0.1082 -0.648999,-0.1029l0.00799998 -0.0158 -0.00799998 0zm4.59589 -1.0605l-0.981298 3.75919 -1.0922 0 0.981398 -3.75919 1.0921 0zm-0.253199 -0.609399c-0.1794,0.00529999 -0.332399,-0.0355999 -0.459099,-0.1227 -0.1266,-0.0870998 -0.1899,-0.1965 -0.1899,-0.328399 0,-0.1319 0.0632999,-0.246699 0.1899,-0.344299 0.1267,-0.0975998 0.279699,-0.1491 0.459099,-0.1543 0.1794,-0.00529999 0.332399,0.0355999 0.458999,0.1226 0.1266,0.0870998 0.1899,0.1966 0.1899,0.328499 0,0.1319 -0.0632999,0.246599 -0.1899,0.344199 -0.1266,0.0976998 -0.279599,0.1491 -0.458999,0.1544zm3.06049 0.672699l-0.245399 1.0288c-0.1371,-0.0790998 -0.266399,-0.1187 -0.387799,-0.1187 -0.279599,0 -0.498599,0.1926 -0.656799,0.577799l-0.593599 2.2001 -1.0922 0 1.0051 -3.73549 1.0922 0 -0.0632999 0.237399c0.258499,-0.1635 0.493299,-0.245299 0.704298,-0.245299 0.0949998,0 0.1742,0.0184 0.237499,0.0553999zm0.440799 3.66429c-0.416899,-0.1794 -0.625299,-0.598899 -0.625299,-1.2584 0,-0.364099 0.0764998,-0.728098 0.2295,-1.0922 0.1794,-0.416799 0.418199,-0.753098 0.716298,-1.009 0.298099,-0.255899 0.594899,-0.383899 0.890298,-0.383899 0.300799,0 0.538199,0.1319 0.712298,0.395799l0.593599 -2.2477 1.0921 0 -1.472 5.61909 -1.1001 0 0.0711998 -0.245299c-0.263799,0.1899 -0.522299,0.284899 -0.775598,0.284899 -0.116,0 -0.2268,-0.0211 -0.332299,-0.0632999zm1.2583 -2.90459c-0.284899,0 -0.527599,0.266499 -0.728098,0.799398 -0.0896998,0.242699 -0.1345,0.474799 -0.1345,0.696398 0,0.369399 0.1002,0.553999 0.300699,0.553999 0.1319,0 0.271699,-0.0817998 0.419499,-0.245299 0.1477,-0.1636 0.268999,-0.374599 0.363999,-0.633199 0.0843998,-0.2163 0.1266,-0.419399 0.1266,-0.609299 0,-0.374699 -0.116,-0.561999 -0.348199,-0.561999z"
				/>
			</svg>
		</fb-menu-item>

		<div class="flex-grow" />

		<fb-menu-item index="1">Processing Center</fb-menu-item>
		<fb-sub-menu index="2">
			<template #title>Workspace</template>
			<fb-menu-item index="2-1">item one</fb-menu-item>
			<fb-menu-item index="2-2">item two</fb-menu-item>
			<fb-menu-item index="2-3">item three</fb-menu-item>
			<fb-sub-menu index="2-4">
				<template #title>item four</template>
				<fb-menu-item index="2-4-1">item one</fb-menu-item>
				<fb-menu-item index="2-4-2">item two</fb-menu-item>
				<fb-menu-item index="2-4-3">item three</fb-menu-item>
			</fb-sub-menu>
		</fb-sub-menu>
	</fb-menu>
</template>

<script lang="ts" setup>
import { ref } from 'vue';

const activeIndex = ref('1');

const handleSelect = (key: string, keyPath: string[]): void => {
	console.log(key, keyPath);
}
</script>

<style scoped>
.flex-grow {
  flex-grow: 1;
}
</style>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbMenu, FbSubMenu, FbMenuItem },
        setup: () => {
            const activeIndex = ref("1");

            const handleSelect = (key: string, keyPath: string[]): void => {
                console.log(key, keyPath);
            };

            return {
                activeIndex,
                handleSelect,
            };
        },
        template: `
<div class="fb-menu-story-block">
	<div class="fb-menu-story-block__item">
		<fb-menu
			:default-active="activeIndex"
			mode="horizontal"
			:ellipsis="false"
			@select="handleSelect"
		>
			<fb-menu-item index="0">
				<svg
					version="1.1"
					xmlns="http://www.w3.org/2000/svg"
					viewBox="0 0 46.4098 10.4641"
					width="150"
					alt="FastyBird logo"
				>
					<path
						style="fill: #d9230f"
						d="M0.677699 6.13129c-0.374299,0 -0.677699,-0.303399 -0.677699,-0.677599 0,-0.374199 0.303399,-0.677699 0.677699,-0.677699 0.947098,0 1.8859,-0.0106 2.83409,-0.00759998 0.1466,-0.965398 0.601499,-1.8298 1.261,-2.48919 0.729298,-0.729398 1.7093,-1.2085 2.79949,-1.297 -0.1232,-0.241299 -0.0932998,-0.543399 0.0979998,-0.757298 0.248499,-0.277699 0.675299,-0.301399 0.952998,-0.0528999l1.5326 1.3707 0.0282999 0.0269999c0.337999,0.1961 0.647799,0.435099 0.922298,0.709498 0.292599,0.292499 0.544999,0.625499 0.747898,0.989698l1.5411 1.432c0.1491,0.1217 0.273399,0.322999 0.325999,0.499299l-1.6534 0c-0.1927,0 -0.351299,0.1585 -0.351299,0.351199l0 0.000299999c0,0.1926 0.1587,0.351299 0.351299,0.351299 0.537099,0 1.0741,0 1.6112,0 -0.0531999,0.1225 -0.1485,0.265899 -0.250599,0.352999l-1.5888 1.4764c-0.0291999,0.0270999 -0.0598999,0.0508999 -0.0921998,0.0719998 -0.1824,0.293899 -0.397799,0.565099 -0.641199,0.808498 -0.810298,0.810498 -1.9301,1.312 -3.16689,1.312 -1.157,0 -2.2955,-0.617199 -3.35279,-1.412l1.4935 0c0.657399,0 1.1908,-0.533299 1.1908,-1.1908 0,-0.657399 -0.533399,-1.1908 -1.1908,-1.1908 -1.8009,0 -3.60179,0 -5.40259,0zm8.31068 2.2577c0.440599,-0.157 0.835798,-0.410199 1.1599,-0.734298 0.565199,-0.564999 0.914798,-1.3461 0.914798,-2.2086 0,-0.862498 -0.349599,-1.6436 -0.914798,-2.2087 -0.565099,-0.565099 -1.3461,-0.914798 -2.2087,-0.914798 -0.862498,0 -1.6436,0.349699 -2.2086,0.914798 -0.413599,0.413699 -0.711798,0.943098 -0.841898,1.5354 0.471799,0.0014 0.943098,0.00319999 1.4149,0.00319999 1.2892,0 2.2028,0.739598 2.61599,1.6303 0.285199,0.615399 0.341499,1.3531 0.0683999,1.9827zm1.2565 -3.68889c0,-0.410899 -0.333099,-0.744098 -0.744098,-0.744098 -0.410899,0 -0.743998,0.333199 -0.743998,0.744098 0,0.410899 0.333099,0.744098 0.743998,0.744098 0.410999,0 0.744098,-0.333199 0.744098,-0.744098zm-4.16459 1.9444c0.374199,0 0.677599,0.303399 0.677599,0.677599 0,0.374199 -0.303399,0.677699 -0.677599,0.677699l-3.68299 0c-0.374199,0 -0.677699,-0.303499 -0.677699,-0.677699 0,-0.374199 0.303499,-0.677599 0.677699,-0.677599l3.68299 0z"
					/>
					<path
						style="fill: #474949"
						d="M19.2432 5.92919l-1.4008 0 -0.759798 2.92829 -1.1792 0 1.2504 -4.79599c-0.1794,0.1055 -0.390399,0.1583 -0.633099,0.1583 -0.0791998,0 -0.1609,-0.00529999 -0.245399,-0.0158l0.1979 -0.759798c0.0685999,0.0105 0.1345,0.0158 0.1979,0.0158 0.342899,0 0.604099,-0.1794 0.783498,-0.538199l3.10229 0 -0.237399 0.815198 -1.9073 0 -0.356199 1.3692 1.4246 0 -0.237399 0.822998zm0.773198 2.92039c-0.427299,-0.1847 -0.640999,-0.609399 -0.640999,-1.2742 0,-0.369299 0.0790998,-0.738698 0.237399,-1.108 0.1741,-0.411499 0.411599,-0.747898 0.712298,-1.009 0.300699,-0.261199 0.601499,-0.391799 0.902198,-0.391799 0.295499,0 0.530299,0.1213 0.704398,0.364099l0.0869998 -0.324499 1.0684 0 -0.688499 2.65909c-0.0105,0.0369999 -0.0158,0.0712998 -0.0158,0.1029 0,0.1055 0.0606999,0.1583 0.182,0.1583 0.0685999,0 0.1451,-0.0158 0.2295,-0.0474999l-0.1978 0.696498c-0.274399,0.1846 -0.527599,0.276999 -0.759798,0.276999 -0.263799,0 -0.485399,-0.1187 -0.664799,-0.356199 -0.274299,0.2111 -0.546099,0.316599 -0.815198,0.316599 -0.1213,0 -0.234699,-0.0211 -0.340299,-0.0632999zm1.3217 -2.93619c-0.1266,0 -0.262499,0.0791998 -0.407599,0.237399 -0.1451,0.1583 -0.265099,0.366699 -0.360099,0.625299 -0.0896998,0.237399 -0.1345,0.466899 -0.1345,0.688499 0,0.353499 0.0922998,0.530299 0.276999,0.530299 0.1213,0 0.255899,-0.0844998 0.403599,-0.253299 0.1477,-0.1688 0.269099,-0.385199 0.364099,-0.648999 0.0948998,-0.237399 0.1424,-0.461599 0.1424,-0.672699 0,-0.337599 -0.0949998,-0.506499 -0.284899,-0.506499zm4.86489 0.261199c-0.300699,-0.2163 -0.654199,-0.324499 -1.0605,-0.324499 -0.0896998,0 -0.1794,0.0289999 -0.269099,0.0870998 -0.0896998,0.0579999 -0.1345,0.1292 -0.1345,0.2136 0,0.0844998 0.1134,0.1847 0.340299,0.300799 0.316599,0.1583 0.527599,0.279599 0.633099,0.363999 0.2216,0.1847 0.332399,0.406299 0.332399,0.664799 0,0.448499 -0.1503,0.808598 -0.451099,1.0803 -0.300699,0.271699 -0.677999,0.407599 -1.1317,0.407599 -0.153,0 -0.369299,-0.0316999 -0.648999,-0.0949998 -0.305999,-0.0632999 -0.503799,-0.1292 -0.593499,-0.1978l0.2216 -0.878498c0.290199,0.1688 0.638399,0.253299 1.0446,0.253299 0.348299,0 0.522399,-0.0976998 0.522399,-0.292899 0,-0.1477 -0.1042,-0.279599 -0.312599,-0.395699 -0.2084,-0.1161 -0.415499,-0.2295 -0.621299,-0.340299 -0.2163,-0.1636 -0.324499,-0.369299 -0.324499,-0.617299 0,-0.406299 0.1583,-0.746598 0.474899,-1.0209 0.300699,-0.258599 0.656899,-0.387799 1.0684,-0.387799 0.469599,0 0.846798,0.0975998 1.1317,0.292799l-0.2216 0.886398zm1.3985 2.68289l-1.0922 0 0.759798 -2.92829 -0.498599 0 0.2137 -0.815098 0.498599 0 0.427399 -1.6224 1.0921 0 -0.419399 1.6224 0.688499 0 -0.2216 0.815098 -0.680599 0 -0.767698 2.92829zm3.32959 0c-0.2268,0.369299 -0.379799,0.606799 -0.458999,0.712298 -0.1635,0.2163 -0.363999,0.406199 -0.601499,0.569799 -0.311299,0.2163 -0.614599,0.324499 -0.910098,0.324499 -0.448499,-0.0211 -0.670099,-0.1636 -0.664799,-0.427399l0.514399 -0.712298c0.00529999,0.1056 0.0817998,0.1557 0.2295,0.1504 0.247999,-0.0211 0.514499,-0.2269 0.799398,-0.617299l-0.364099 -3.75129 1.0922 0 0.2137 2.2001 1.3454 -2.1922 1.0921 0 -2.2872 3.74339zm4.02599 -2.69079l0.1742 -0.720198c0.0473999,0.00529999 0.0790998,0.00789998 0.0948998,0.00789998 0.1003,0 0.1583,0 0.1741,0 0.517099,-0.0368999 0.828398,-0.324499 0.933898,-0.862698 0.0106,-0.0526999 0.0158,-0.1002 0.0158,-0.1424 0,-0.284899 -0.1134,-0.490699 -0.340299,-0.617299 -0.1635,-0.0949998 -0.334999,-0.1319 -0.514399,-0.1108 -0.0632999,0.0158 -0.1081,0.0211 -0.1345,0.0158l-1.3375 5.12049 -1.2584 0 1.2505 -4.79599c-0.1794,0.1055 -0.390499,0.1583 -0.633199,0.1583 -0.0790998,0 -0.1609,-0.00529999 -0.245299,-0.0158l0.1978 -0.759798c0.0685999,0.0105 0.1346,0.0158 0.1979,0.0158 0.342899,0 0.604099,-0.1794 0.783498,-0.538199l1.0209 -0.00789998c0.886398,-0.0105 1.5011,0.2243 1.844,0.704398 0.1742,0.2216 0.261199,0.477499 0.261199,0.767698 0,0.274299 -0.0738998,0.539499 -0.2216,0.795398 -0.1477,0.255799 -0.342899,0.457699 -0.585599,0.605399 0.342899,0.284899 0.514399,0.672699 0.514399,1.1634 0,0.295399 -0.0711998,0.593499 -0.2137,0.894298 -0.1688,0.348199 -0.394399,0.617299 -0.676699,0.807198 -0.282199,0.19 -0.576399,0.284899 -0.882398,0.284899 -0.2163,0 -0.422099,-0.0473999 -0.617299,-0.1424 -0.0579999,-0.0263999 -0.2031,-0.1003 -0.435299,-0.2216l0.2137 -0.870598c0.0421999,0.0474999 0.0896998,0.0896998 0.1425,0.1267 0.1213,0.0895998 0.250599,0.1398 0.387699,0.1503 0.0422999,0.0159 0.0949998,0.0237999 0.1583,0.0237999 0.1689,0 0.335099,-0.0606999 0.498599,-0.1821 0.19,-0.1477 0.308699,-0.340299 0.356199,-0.577699 0.0158,-0.0896998 0.0236999,-0.1715 0.0236999,-0.245299 0,-0.342999 -0.1662,-0.580399 -0.498599,-0.712298 -0.1847,-0.0738998 -0.400999,-0.1082 -0.648999,-0.1029l0.00799998 -0.0158 -0.00799998 0zm4.59589 -1.0605l-0.981298 3.75919 -1.0922 0 0.981398 -3.75919 1.0921 0zm-0.253199 -0.609399c-0.1794,0.00529999 -0.332399,-0.0355999 -0.459099,-0.1227 -0.1266,-0.0870998 -0.1899,-0.1965 -0.1899,-0.328399 0,-0.1319 0.0632999,-0.246699 0.1899,-0.344299 0.1267,-0.0975998 0.279699,-0.1491 0.459099,-0.1543 0.1794,-0.00529999 0.332399,0.0355999 0.458999,0.1226 0.1266,0.0870998 0.1899,0.1966 0.1899,0.328499 0,0.1319 -0.0632999,0.246599 -0.1899,0.344199 -0.1266,0.0976998 -0.279599,0.1491 -0.458999,0.1544zm3.06049 0.672699l-0.245399 1.0288c-0.1371,-0.0790998 -0.266399,-0.1187 -0.387799,-0.1187 -0.279599,0 -0.498599,0.1926 -0.656799,0.577799l-0.593599 2.2001 -1.0922 0 1.0051 -3.73549 1.0922 0 -0.0632999 0.237399c0.258499,-0.1635 0.493299,-0.245299 0.704298,-0.245299 0.0949998,0 0.1742,0.0184 0.237499,0.0553999zm0.440799 3.66429c-0.416899,-0.1794 -0.625299,-0.598899 -0.625299,-1.2584 0,-0.364099 0.0764998,-0.728098 0.2295,-1.0922 0.1794,-0.416799 0.418199,-0.753098 0.716298,-1.009 0.298099,-0.255899 0.594899,-0.383899 0.890298,-0.383899 0.300799,0 0.538199,0.1319 0.712298,0.395799l0.593599 -2.2477 1.0921 0 -1.472 5.61909 -1.1001 0 0.0711998 -0.245299c-0.263799,0.1899 -0.522299,0.284899 -0.775598,0.284899 -0.116,0 -0.2268,-0.0211 -0.332299,-0.0632999zm1.2583 -2.90459c-0.284899,0 -0.527599,0.266499 -0.728098,0.799398 -0.0896998,0.242699 -0.1345,0.474799 -0.1345,0.696398 0,0.369399 0.1002,0.553999 0.300699,0.553999 0.1319,0 0.271699,-0.0817998 0.419499,-0.245299 0.1477,-0.1636 0.268999,-0.374599 0.363999,-0.633199 0.0843998,-0.2163 0.1266,-0.419399 0.1266,-0.609299 0,-0.374699 -0.116,-0.561999 -0.348199,-0.561999z"
					/>
				</svg>
			</fb-menu-item>

			<div class="fb-menu-story-block__items-space" />

			<fb-menu-item index="1">Processing Center</fb-menu-item>
			<fb-sub-menu index="2">
				<template #title>Workspace</template>
				<fb-menu-item index="2-1">item one</fb-menu-item>
				<fb-menu-item index="2-2">item two</fb-menu-item>
				<fb-menu-item index="2-3">item three</fb-menu-item>
				<fb-sub-menu index="2-4">
					<template #title>item four</template>
					<fb-menu-item index="2-4-1">item one</fb-menu-item>
					<fb-menu-item index="2-4-2">item two</fb-menu-item>
					<fb-menu-item index="2-4-3">item three</fb-menu-item>
				</fb-sub-menu>
			</fb-sub-menu>
		</fb-menu>
	</div>
</div>`,
    }),
};

export const SideBar: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-row>
		<fb-col :span="12">
			<h5>Default colors</h5>

			<fb-menu
				default-active="2"
				@open="handleOpen"
				@close="handleClose"
			>
				<fb-sub-menu index="1">
					<template #title>
						<fb-icon><fas-location-dot /></fb-icon>
						<span>Navigator One</span>
					</template>
					<fb-menu-item-group title="Group One">
						<fb-menu-item index="1-1">item one</fb-menu-item>
						<fb-menu-item index="1-2">item two</fb-menu-item>
					</fb-menu-item-group>
					<fb-menu-item-group title="Group Two">
						<fb-menu-item index="1-3">item three</fb-menu-item>
					</fb-menu-item-group>
					<fb-sub-menu index="1-4">
						<template #title>item four</template>
						<fb-menu-item index="1-4-1">item one</fb-menu-item>
					</fb-sub-menu>
				</fb-sub-menu>
				<fb-menu-item index="2">
					<fb-icon><fas-bars /></fb-icon>
					<span>Navigator Two</span>
				</fb-menu-item>
				<fb-menu-item index="3" disabled>
					<fb-icon><far-file-lines /></fb-icon>
					<span>Navigator Three</span>
				</fb-menu-item>
				<fb-menu-item index="4">
					<fb-icon><fas-gear /></fb-icon>
					<span>Navigator Four</span>
				</fb-menu-item>
			</fb-menu>
		</fb-col>

		<fb-col :span="12">
			<h5>Custom colors</h5>

			<fb-menu
				active-text-color="#ffd04b"
				background-color="#545c64"
				default-active="2"
				text-color="#fff"
				@open="handleOpen"
				@close="handleClose"
			>
				<fb-sub-menu index="1">
					<template #title>
						<fb-icon><fas-location-dot /></fb-icon>
						<span>Navigator One</span>
					</template>
					<fb-menu-item-group title="Group One">
						<fb-menu-item index="1-1">item one</fb-menu-item>
						<fb-menu-item index="1-2">item two</fb-menu-item>
					</fb-menu-item-group>
					<fb-menu-item-group title="Group Two">
						<fb-menu-item index="1-3">item three</fb-menu-item>
					</fb-menu-item-group>
					<fb-sub-menu index="1-4">
						<template #title>item four</template>
						<fb-menu-item index="1-4-1">item one</fb-menu-item>
					</fb-sub-menu>
				</fb-sub-menu>
				<fb-menu-item index="2">
					<fb-icon><fas-bars /></fb-icon>
					<span>Navigator Two</span>
				</fb-menu-item>
				<fb-menu-item index="3" disabled>
					<fb-icon><far-file-lines /></fb-icon>
					<span>Navigator Three</span>
				</fb-menu-item>
				<fb-menu-item index="4">
					<fb-icon><fas-gear /></fb-icon>
					<span>Navigator Four</span>
				</fb-menu-item>
			</fb-menu>
		</fb-col>
	</fb-row>
</template>

<script lang="ts" setup>
import { FasLocationDot, FasBars, FarFileLines, FasGear } from '@fastybird/web-ui-icons';

const handleOpen = (key: string, keyPath: string[]): void => {
	console.log(key, keyPath)
}

const handleClose = (key: string, keyPath: string[]): void => {
	console.log(key, keyPath);
}
</script>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbMenu, FbSubMenu, FbMenuItem, FbMenuItemGroup, FbRow, FbCol, FbIcon, FasLocationDot, FasBars, FarFileLines, FasGear },
        setup: () => {
            const handleOpen = (key: string, keyPath: string[]): void => {
                console.log(key, keyPath);
            };

            const handleClose = (key: string, keyPath: string[]): void => {
                console.log(key, keyPath);
            };

            return {
                handleOpen,
                handleClose,
            };
        },
        template: `
<div class="fb-menu-story-block">
	<div class="fb-menu-story-block__item">
		<fb-row>
			<fb-col :span="12">
				<h5>Default colors</h5>

				<fb-menu
					default-active="2"
					@open="handleOpen"
					@close="handleClose"
				>
					<fb-sub-menu index="1">
						<template #title>
							<fb-icon><fas-location-dot /></fb-icon>
							<span>Navigator One</span>
						</template>
						<fb-menu-item-group title="Group One">
							<fb-menu-item index="1-1">item one</fb-menu-item>
							<fb-menu-item index="1-2">item two</fb-menu-item>
						</fb-menu-item-group>
						<fb-menu-item-group title="Group Two">
							<fb-menu-item index="1-3">item three</fb-menu-item>
						</fb-menu-item-group>
						<fb-sub-menu index="1-4">
							<template #title>item four</template>
							<fb-menu-item index="1-4-1">item one</fb-menu-item>
						</fb-sub-menu>
					</fb-sub-menu>
					<fb-menu-item index="2">
						<fb-icon><fas-bars /></fb-icon>
						<span>Navigator Two</span>
					</fb-menu-item>
					<fb-menu-item index="3" disabled>
						<fb-icon><far-file-lines /></fb-icon>
						<span>Navigator Three</span>
					</fb-menu-item>
					<fb-menu-item index="4">
						<fb-icon><fas-gear /></fb-icon>
						<span>Navigator Four</span>
					</fb-menu-item>
				</fb-menu>
			</fb-col>

			<fb-col :span="12">
				<h5>Custom colors</h5>

				<fb-menu
					active-text-color="#ffd04b"
					background-color="#545c64"
					default-active="2"
					text-color="#fff"
					@open="handleOpen"
					@close="handleClose"
				>
					<fb-sub-menu index="1">
						<template #title>
							<fb-icon><fas-location-dot /></fb-icon>
							<span>Navigator One</span>
						</template>
						<fb-menu-item-group title="Group One">
							<fb-menu-item index="1-1">item one</fb-menu-item>
							<fb-menu-item index="1-2">item two</fb-menu-item>
						</fb-menu-item-group>
						<fb-menu-item-group title="Group Two">
							<fb-menu-item index="1-3">item three</fb-menu-item>
						</fb-menu-item-group>
						<fb-sub-menu index="1-4">
							<template #title>item four</template>
							<fb-menu-item index="1-4-1">item one</fb-menu-item>
						</fb-sub-menu>
					</fb-sub-menu>
					<fb-menu-item index="2">
						<fb-icon><fas-bars /></fb-icon>
						<span>Navigator Two</span>
					</fb-menu-item>
					<fb-menu-item index="3" disabled>
						<fb-icon><far-file-lines /></fb-icon>
						<span>Navigator Three</span>
					</fb-menu-item>
					<fb-menu-item index="4">
						<fb-icon><fas-gear /></fb-icon>
						<span>Navigator Four</span>
					</fb-menu-item>
				</fb-menu>
			</fb-col>
		</fb-row>
	</div>
</div>`,
    }),
};

export const Collapse: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-radio-group v-model="isCollapse" style="margin-bottom: 20px">
		<fb-radio-button :value="false">expand</fb-radio-button>
		<fb-radio-button :value="true">collapse</fb-radio-button>
	</fb-radio-group>

		<fb-menu
			default-active="2"
			class="fb-menu-vertical-demo"
			:collapse="isCollapse"
			@open="handleOpen"
			@close="handleClose"
		>
			<fb-sub-menu index="1">
				<template #title>
					<fb-icon><fas-location-dot /></fb-icon>
					<span>Navigator One</span>
				</template>
				<fb-menu-item-group>
					<template #title><span>Group One</span></template>
					<fb-menu-item index="1-1">item one</fb-menu-item>
					<fb-menu-item index="1-2">item two</fb-menu-item>
				</fb-menu-item-group>
				<fb-menu-item-group title="Group Two">
					<fb-menu-item index="1-3">item three</fb-menu-item>
				</fb-menu-item-group>
				<fb-sub-menu index="1-4">
					<template #title><span>item four</span></template>
					<fb-menu-item index="1-4-1">item one</fb-menu-item>
				</fb-sub-menu>
			</fb-sub-menu>
			<fb-menu-item index="2">
				<fb-icon><fas-bars /></fb-icon>
				<template #title>Navigator Two</template>
			</fb-menu-item>
			<fb-menu-item index="3" disabled>
				<fb-icon><far-file-lines /></fb-icon>
				<template #title>Navigator Three</template>
			</fb-menu-item>
			<fb-menu-item index="4">
				<fb-icon><fas-gear /></fb-icon>
				<template #title>Navigator Four</template>
			</fb-menu-item>
		</fb-menu>
</template>

<script lang="ts" setup>
import { ref } from 'vue';
import { FasLocationDot, FasBars, FarFileLines, FasGear } from '@fastybird/web-ui-icons';

const isCollapse = ref(true);

const handleOpen = (key: string, keyPath: string[]): void => {
	console.log(key, keyPath);
}

const handleClose = (key: string, keyPath: string[]): void => {
	console.log(key, keyPath);
}
</script>

<style scoped>
.fb-menu-vertical-demo:not(.fb-menu--collapse) {
  width: 200px;
  min-height: 400px;
}
</style>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: {
            FbMenu,
            FbSubMenu,
            FbMenuItem,
            FbMenuItemGroup,
            FbIcon,
            FbRadioGroup,
            FbRadioButton,
            FasLocationDot,
            FasBars,
            FarFileLines,
            FasGear,
        },
        setup: () => {
            const isCollapse = ref(true);

            const handleOpen = (key: string, keyPath: string[]): void => {
                console.log(key, keyPath);
            };

            const handleClose = (key: string, keyPath: string[]): void => {
                console.log(key, keyPath);
            };

            return {
                isCollapse,
                handleOpen,
                handleClose,
            };
        },
        template: `
<div class="fb-menu-story-block">
	<fb-radio-group v-model="isCollapse" style="margin-bottom: 20px">
		<fb-radio-button :value="false">expand</fb-radio-button>
		<fb-radio-button :value="true">collapse</fb-radio-button>
	</fb-radio-group>

	<div class="fb-menu-story-block__item">
		<fb-menu
			default-active="2"
			class="fb-menu-story-block__vertical-demo"
			:collapse="isCollapse"
			@open="handleOpen"
			@close="handleClose"
		>
			<fb-sub-menu index="1">
				<template #title>
					<fb-icon><fas-location-dot /></fb-icon>
					<span>Navigator One</span>
				</template>
				<fb-menu-item-group>
					<template #title><span>Group One</span></template>
					<fb-menu-item index="1-1">item one</fb-menu-item>
					<fb-menu-item index="1-2">item two</fb-menu-item>
				</fb-menu-item-group>
				<fb-menu-item-group title="Group Two">
					<fb-menu-item index="1-3">item three</fb-menu-item>
				</fb-menu-item-group>
				<fb-sub-menu index="1-4">
					<template #title><span>item four</span></template>
					<fb-menu-item index="1-4-1">item one</fb-menu-item>
				</fb-sub-menu>
			</fb-sub-menu>
			<fb-menu-item index="2">
				<fb-icon><fas-bars /></fb-icon>
				<template #title>Navigator Two</template>
			</fb-menu-item>
			<fb-menu-item index="3" disabled>
				<fb-icon><far-file-lines /></fb-icon>
				<template #title>Navigator Three</template>
			</fb-menu-item>
			<fb-menu-item index="4">
				<fb-icon><fas-gear /></fb-icon>
				<template #title>Navigator Four</template>
			</fb-menu-item>
		</fb-menu>
	</div>
</div>`,
    }),
};

export const PopperOffset: Story = {
    parameters: {
        docs: {
            source: {
                code: `
<template>
	<fb-menu
		ellipsis
		mode="horizontal"
		:popper-offset="16"
		class="fb-menu-story-block__popper-demo"
	>
		<fb-menu-item index="1">Processing Center</fb-menu-item>
		<fb-sub-menu index="2">
			<template #title>Workspace</template>
			<fb-menu-item index="2-1">item one</fb-menu-item>
			<fb-menu-item index="2-2">item two</fb-menu-item>
			<fb-menu-item index="2-3">item three</fb-menu-item>
			<fb-sub-menu index="2-4">
				<template #title>item four</template>
				<fb-menu-item index="2-4-1">item one</fb-menu-item>
				<fb-menu-item index="2-4-2">item two</fb-menu-item>
				<fb-menu-item index="2-4-3">item three</fb-menu-item>
			</fb-sub-menu>
		</fb-sub-menu>
		<fb-sub-menu index="3" :popper-offset="-10">
			<template #title>Override Popper Offset</template>
			<fb-menu-item index="3-1">item one</fb-menu-item>
			<fb-menu-item index="3-2">item two</fb-menu-item>
			<fb-menu-item index="3-3">item three</fb-menu-item>
			<fb-sub-menu index="3-4" :popper-offset="20">
				<template #title>override child</template>
				<fb-menu-item index="3-4-1">item one</fb-menu-item>
				<fb-menu-item index="3-4-2">item two</fb-menu-item>
				<fb-menu-item index="3-4-3">item three</fb-menu-item>
			</fb-sub-menu>
		</fb-sub-menu>
		<fb-menu-item index="4" disabled>Info</fb-menu-item>
		<fb-menu-item index="5">Orders</fb-menu-item>
	</fb-menu>
</template>`,
            },
        },
    },
    tags: ["hideInSidebar"],
    render: () => ({
        components: { FbMenu, FbSubMenu, FbMenuItem },
        template: `
<div class="fb-menu-story-block">
	<div class="fb-menu-story-block__item">
		<fb-menu
			ellipsis
			mode="horizontal"
			:popper-offset="16"
			class="fb-menu-story-block__popper-demo"
		>
			<fb-menu-item index="1">Processing Center</fb-menu-item>
			<fb-sub-menu index="2">
				<template #title>Workspace</template>
				<fb-menu-item index="2-1">item one</fb-menu-item>
				<fb-menu-item index="2-2">item two</fb-menu-item>
				<fb-menu-item index="2-3">item three</fb-menu-item>
				<fb-sub-menu index="2-4">
					<template #title>item four</template>
					<fb-menu-item index="2-4-1">item one</fb-menu-item>
					<fb-menu-item index="2-4-2">item two</fb-menu-item>
					<fb-menu-item index="2-4-3">item three</fb-menu-item>
				</fb-sub-menu>
			</fb-sub-menu>
			<fb-sub-menu index="3" :popper-offset="-10">
				<template #title>Override Popper Offset</template>
				<fb-menu-item index="3-1">item one</fb-menu-item>
				<fb-menu-item index="3-2">item two</fb-menu-item>
				<fb-menu-item index="3-3">item three</fb-menu-item>
				<fb-sub-menu index="3-4" :popper-offset="20">
					<template #title>override child</template>
					<fb-menu-item index="3-4-1">item one</fb-menu-item>
					<fb-menu-item index="3-4-2">item two</fb-menu-item>
					<fb-menu-item index="3-4-3">item three</fb-menu-item>
				</fb-sub-menu>
			</fb-sub-menu>
			<fb-menu-item index="4" disabled>Info</fb-menu-item>
			<fb-menu-item index="5">Orders</fb-menu-item>
		</fb-menu>
	</div>
</div>`,
    }),
};
