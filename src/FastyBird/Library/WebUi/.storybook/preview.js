import './storybook-styles.css';
import {app} from '@storybook/vue3';

import {library} from '@fortawesome/fontawesome-svg-core'
import {
    faAngleLeft,
    faAngleRight,
    faBell,
    faChartBar,
    faClose,
    faCog,
    faEnvelope,
    faExclamation,
    faHeart,
    faInfo,
    faInfoCircle,
    faMagic,
    faMugHot,
    faNewspaper,
    faPaperPlane,
    faPencilAlt,
    faTimes,
    faTrash,
    faUser,
    faUserSecret,
} from '@fortawesome/free-solid-svg-icons'
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome'

library.add(faAngleLeft)
library.add(faAngleRight)
library.add(faBell)
library.add(faChartBar)
library.add(faClose)
library.add(faCog)
library.add(faEnvelope)
library.add(faExclamation)
library.add(faHeart)
library.add(faInfo)
library.add(faInfoCircle)
library.add(faMagic)
library.add(faMugHot)
library.add(faNewspaper)
library.add(faPaperPlane)
library.add(faPencilAlt)
library.add(faTimes)
library.add(faTrash)
library.add(faUser)
library.add(faUserSecret)

app.component('FontAwesomeIcon', FontAwesomeIcon)

export const parameters = {
    viewMode: 'docs',
    actions: {argTypesRegex: "^on[A-Z].*"},
    controls: {
        matchers: {
            color: /(background|color)$/i,
            date: /Date$/,
        },
    },
}
