import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import { defineComponent, h } from 'vue'
import App from '../App.vue'

describe('App', () => {
  it('renders the active route', async () => {
    const TestView = defineComponent({
      render: () => h('p', 'Router is working'),
    })

    const router = createRouter({
      history: createMemoryHistory(),
      routes: [{ path: '/', component: TestView }],
    })

    await router.push('/')
    await router.isReady()

    const wrapper = mount(App, {
      global: { plugins: [router] },
    })

    expect(wrapper.text()).toContain('Router is working')
  })
})
