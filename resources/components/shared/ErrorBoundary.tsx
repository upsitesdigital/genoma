import { Component, type ReactNode } from 'react'

interface Props {
  children: ReactNode
  fallback?: ReactNode
}

interface State {
  hasError: boolean
  error: Error | null
}

export default class ErrorBoundary extends Component<Props, State> {
  state: State = { hasError: false, error: null }

  static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error }
  }

  componentDidCatch(error: Error, info: React.ErrorInfo) {
    console.error('[ErrorBoundary]', error, info.componentStack)
  }

  render() {
    if (this.state.hasError) {
      return (
        this.props.fallback ?? (
          <div className="container py-16 text-center">
            <h2 className="text-xl font-semibold text-destructive">Algo deu errado</h2>
            <p className="mt-2 text-sm text-muted-foreground">{this.state.error?.message}</p>
          </div>
        )
      )
    }
    return this.props.children
  }
}
