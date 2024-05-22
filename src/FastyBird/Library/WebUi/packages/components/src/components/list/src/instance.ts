import type List from './list.vue';
import type ListItem from './item.vue';

export type ListInstance = InstanceType<typeof List>;
export type ListItemInstance = InstanceType<typeof ListItem>;
