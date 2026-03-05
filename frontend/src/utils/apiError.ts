import axios from 'axios'

type ApiErrorPayload = {
  message?: string
  errors?: Record<string, string[] | string>
}

const extractValidationErrors = (errors?: Record<string, string[] | string>) => {
  if (!errors) {
    return ''
  }

  const messages = Object.values(errors)
    .flatMap((value) => (Array.isArray(value) ? value : [value]))
    .filter(Boolean)

  return messages.slice(0, 3).join(' ')
}

export const getApiErrorMessage = (error: unknown, fallbackMessage: string) => {
  if (!axios.isAxiosError<ApiErrorPayload>(error)) {
    return fallbackMessage
  }

  if (!error.response) {
    return 'Network error. Please check your connection and try again.'
  }

  const payload = error.response.data
  const validationMessage = extractValidationErrors(payload?.errors)

  if (validationMessage) {
    return validationMessage
  }

  if (payload?.message && payload.message !== 'Server Error') {
    return payload.message
  }

  if (error.response.status === 401) {
    return 'Your session is invalid or expired. Please login again.'
  }

  if (error.response.status >= 500) {
    return 'Server error occurred. Please try again in a moment.'
  }

  return fallbackMessage
}
