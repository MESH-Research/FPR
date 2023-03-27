import {
  installQuasarPlugin
} from "@quasar/quasar-app-extension-testing-unit-vitest"
import { ApolloClients } from "@vue/apollo-composable"
import { mount, flushPromises } from "@vue/test-utils"
import { omit } from "lodash"
import { createMockClient } from "test/vitest/apolloClient"
import { CREATE_USER, LOGIN } from "src/graphql/mutations"
import { describe, expect, it, test, vi } from 'vitest'
import RegisterPage from "./RegisterPage.vue"
vi.mock("quasar", () => ({
  ...vi.requireActual("quasar"),
  SessionStorage: {
    remove: () => {},
    getItem: () => null,
  },
}))

vi.mock("vue-router", () => ({
  useRouter: () => ({
    push: vi.fn(),
  }),
}))

installQuasarPlugin()
describe("RegisterPage", () => {
  const wrapperFactory = (mocks = []) => {
    const mockClient = createMockClient()

    mocks?.forEach((mock) => {
      mockClient.setRequestHandler(...mock)
    })

    return {
      wrapper: mount(RegisterPage, {
        global: {
          provide: {
            [ApolloClients]: { default: mockClient },
          },
          stubs: ["router-link", "i18n-t"],
          mocks: {
            $t: (token) => token,
          },
        },
      }),
      mockClient,
    }
  }

  it("mounts without errors", () => {
    expect(wrapperFactory().wrapper).toBeTruthy()
  })

  test("form submits on valid data", async () => {
    const { wrapper, mockClient } = wrapperFactory()

    const user = {
      username: "user",
      password: "albancub4Grac&",
      name: "Joe Doe",
      email: "test@example.com",
      created_at: "nowish",
    }

    const mutateHandler = vi
      .fn()
      .mockResolvedValue({ data: { createUser: { id: 1, ...user } } })

    mockClient.setRequestHandler(CREATE_USER, mutateHandler)
    mockClient.setRequestHandler(
      LOGIN,
      vi.fn().mockResolvedValue({
        data: { login: { id: 1, ...user } },
      })
    )

    await wrapper.findComponent({ ref: "nameInput" }).setValue(user.name)
    await wrapper.findComponent({ ref: "emailInput" }).setValue(user.email)
    await wrapper
      .findComponent({ ref: "usernameInput" })
      .setValue(user.username)

    await wrapper
      .findComponent({ ref: "passwordInput" })
      .setValue(user.password)

    wrapper.findComponent({ name: "q-btn" }).trigger("submit")
    await flushPromises()

    expect(wrapper.vm.formErrorMsg).toBeFalsy()
    expect(mutateHandler).toHaveBeenCalledWith(
      expect.objectContaining(omit(user, "created_at"))
    )
  })
})
