import { useState } from 'react'
import { useQuery, useMutation } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { api } from '@/lib/api'
import { cn } from '@/lib/cn'

// ─── Types ───────────────────────────────────────────────────────────────────

type FieldType = 'text' | 'email' | 'phone' | 'number' | 'textarea' | 'select' | 'radio' | 'checkbox'

interface FormField {
  type: FieldType
  name: string
  label: string
  placeholder: string
  required: boolean
  options: string[]
}

interface FormSchema {
  slug: string
  title: string
  fields: FormField[]
  submitLabel: string
  successMessage: string
}

// ─── DynamicForm (entry point) ───────────────────────────────────────────────

interface DynamicFormProps {
  slug: string
  className?: string
}

export function DynamicForm({ slug, className }: DynamicFormProps) {
  const { data: schema, isLoading, error } = useQuery<FormSchema>({
    queryKey: ['form', slug],
    queryFn: () => api<FormSchema>(`/forms/${slug}`),
    staleTime: Infinity,
  })

  if (isLoading) return <FormSkeleton />
  if (error || !schema) return (
    <p className="text-sm text-muted-foreground">Formulário não disponível.</p>
  )

  return <FormRenderer schema={schema} className={className} />
}

// ─── Schema Zod dinâmico ─────────────────────────────────────────────────────

function buildSchema(fields: FormField[]) {
  const shape: Record<string, z.ZodTypeAny> = {}

  for (const field of fields) {
    let rule: z.ZodTypeAny

    switch (field.type) {
      case 'email':
        rule = z.string().email('E-mail inválido')
        break
      case 'number':
        rule = z.string().regex(/^\d+$/, 'Apenas números')
        break
      default:
        rule = z.string()
    }

    if (field.required) {
      rule = (rule as z.ZodString).min(1, `${field.label} é obrigatório`)
    } else {
      rule = rule.optional().or(z.literal(''))
    }

    shape[field.name] = rule
  }

  return z.object(shape)
}

// ─── FormRenderer ─────────────────────────────────────────────────────────────

function FormRenderer({ schema, className }: { schema: FormSchema; className?: string }) {
  const [success, setSuccess] = useState(false)

  const zodSchema = buildSchema(schema.fields)
  type FormValues = z.infer<typeof zodSchema>

  const form = useForm<FormValues>({
    resolver: zodResolver(zodSchema),
    defaultValues: Object.fromEntries(schema.fields.map((f) => [f.name, ''])) as FormValues,
  })

  const mutation = useMutation({
    mutationFn: (data: FormValues) =>
      api(`/forms/${schema.slug}/submit`, {
        method: 'POST',
        body: JSON.stringify(data),
      }),
    onSuccess: () => setSuccess(true),
  })

  if (success) {
    return (
      <div className="rounded-md border border-border bg-muted/40 p-6 text-center">
        <p className="text-muted-foreground">{schema.successMessage}</p>
      </div>
    )
  }

  return (
    <form
      className={cn('space-y-5', className)}
      onSubmit={form.handleSubmit((data) => mutation.mutate(data))}
      noValidate
    >
      {schema.fields.map((field) => (
        <FieldInput key={field.name} field={field} form={form} />
      ))}

      {mutation.isError && (
        <p className="text-sm text-destructive">
          Erro ao enviar. Tente novamente.
        </p>
      )}

      <button
        type="submit"
        disabled={mutation.isPending}
        className="inline-flex items-center justify-center rounded-md bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow transition-opacity hover:opacity-90 disabled:opacity-50"
      >
        {mutation.isPending ? 'Enviando…' : schema.submitLabel}
      </button>
    </form>
  )
}

// ─── FieldInput ───────────────────────────────────────────────────────────────

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function FieldInput({ field, form }: { field: FormField; form: any }) {
  const error: string | undefined = form.formState.errors[field.name]?.message
  const inputClass = cn(
    'w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground',
    'focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-1',
    error && 'border-destructive focus:ring-destructive'
  )

  return (
    <div className="space-y-1.5">
      <label className="block text-sm font-medium" htmlFor={field.name}>
        {field.label}
        {field.required && <span className="ml-1 text-destructive">*</span>}
      </label>

      {field.type === 'textarea' ? (
        <textarea
          id={field.name}
          rows={4}
          placeholder={field.placeholder}
          className={inputClass}
          {...form.register(field.name)}
        />
      ) : field.type === 'select' ? (
        <select id={field.name} className={inputClass} {...form.register(field.name)}>
          <option value="">{field.placeholder || 'Selecione…'}</option>
          {field.options.map((opt) => (
            <option key={opt} value={opt}>{opt}</option>
          ))}
        </select>
      ) : field.type === 'radio' ? (
        <div className="space-y-2">
          {field.options.map((opt) => (
            <label key={opt} className="flex items-center gap-2 text-sm cursor-pointer">
              <input type="radio" value={opt} {...form.register(field.name)} />
              {opt}
            </label>
          ))}
        </div>
      ) : field.type === 'checkbox' ? (
        <div className="space-y-2">
          {field.options.map((opt) => (
            <label key={opt} className="flex items-center gap-2 text-sm cursor-pointer">
              <input type="checkbox" value={opt} {...form.register(field.name)} />
              {opt}
            </label>
          ))}
        </div>
      ) : (
        <input
          id={field.name}
          type={field.type === 'phone' ? 'tel' : field.type}
          placeholder={field.placeholder}
          className={inputClass}
          {...form.register(field.name)}
        />
      )}

      {error && <p className="text-xs text-destructive">{error}</p>}
    </div>
  )
}

// ─── Skeleton ────────────────────────────────────────────────────────────────

function FormSkeleton() {
  return (
    <div className="space-y-5">
      {[1, 2, 3].map((i) => (
        <div key={i} className="space-y-1.5">
          <div className="h-4 w-24 animate-pulse rounded bg-muted" />
          <div className="h-10 w-full animate-pulse rounded bg-muted" />
        </div>
      ))}
      <div className="h-10 w-28 animate-pulse rounded bg-muted" />
    </div>
  )
}
