import { ofetch } from 'ofetch'

export const $api = ofetch.create({
  baseURL: (import.meta.env.VITE_API_BASE_URL && String(import.meta.env.VITE_API_BASE_URL)) || '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  async onRequest({ options }) {
    const accessToken = useCookie('accessToken').value
    if (accessToken) {
      options.headers = {
        ...options.headers,
        Authorization: `Bearer ${accessToken}`,
      }
    }
  },
})
