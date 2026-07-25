import { Head, useForm } from '@inertiajs/react';
import { Check, ExternalLink, RefreshCw } from 'lucide-react';
import { useMemo, useState } from 'react';
import aiModels from '@/actions/App/Http/Controllers/Settings/AiModelsController';
import ai from '@/actions/App/Http/Controllers/Settings/AiSettingsController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { edit } from '@/routes/ai';

type ProviderOption = {
    value: string;
    label: string;
    default_model: string;
    key_url: string;
    model: string;
    has_key: boolean;
    is_active: boolean;
};

type Form = {
    provider: string;
    model: string;
    api_key: string;
};

export default function AiSettings({
    providers,
    active,
}: {
    providers: ProviderOption[];
    active: string;
}) {
    const byValue = useMemo(
        () => new Map(providers.map((provider) => [provider.value, provider])),
        [providers],
    );

    const current = byValue.get(active) ?? providers[0];

    const form = useForm<Form>({
        provider: current.value,
        model: current.model,
        api_key: '',
    });

    const { data, setData, errors, processing, recentlySuccessful } = form;
    const selected = byValue.get(data.provider) ?? current;

    const [models, setModels] = useState<string[]>([]);
    const [refreshing, setRefreshing] = useState(false);
    const [refreshNote, setRefreshNote] = useState<string | null>(null);

    const switchProvider = (value: string) => {
        const next = byValue.get(value);

        setData({
            provider: value,
            model: next?.model ?? next?.default_model ?? '',
            api_key: '',
        });
        setModels([]);
        setRefreshNote(null);
    };

    const refreshModels = async () => {
        setRefreshing(true);
        setRefreshNote(null);

        try {
            const response = await fetch(aiModels.url(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': decodeURIComponent(
                        document.cookie
                            .split('; ')
                            .find((row) => row.startsWith('XSRF-TOKEN='))
                            ?.split('=')[1] ?? '',
                    ),
                },
                body: JSON.stringify({
                    provider: data.provider,
                    api_key: data.api_key || null,
                }),
            });

            const payload: { message: string; models: string[] } =
                await response.json();

            setModels(payload.models);
            setRefreshNote(payload.message);

            if (
                payload.models.length > 0 &&
                !payload.models.includes(data.model)
            ) {
                setData('model', payload.models[0]);
            }
        } catch {
            setRefreshNote('Could not reach the provider.');
        } finally {
            setRefreshing(false);
        }
    };

    return (
        <>
            <Head title="AI assistant" />

            <h1 className="sr-only">AI assistant settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="AI assistant"
                    description="Choose which model writes order insights, and store its API key"
                />

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.put(ai.update.url(), { preserveScroll: true });
                    }}
                    className="max-w-xl space-y-6"
                >
                    <div className="flex flex-col gap-1.5">
                        <Label className="text-sm font-medium text-foreground">
                            Provider
                        </Label>
                        <Select
                            value={data.provider}
                            onValueChange={switchProvider}
                        >
                            <SelectTrigger className="w-full border-border bg-background text-sm">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {providers.map((provider) => (
                                    <SelectItem
                                        key={provider.value}
                                        value={provider.value}
                                    >
                                        {provider.label}
                                        {provider.is_active ? ' · active' : ''}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {errors.provider ? (
                            <p className="text-xs text-destructive">
                                {errors.provider}
                            </p>
                        ) : null}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <div className="flex items-center justify-between gap-3">
                            <Label className="text-sm font-medium text-foreground">
                                API key
                            </Label>
                            <a
                                href={selected.key_url}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                            >
                                Get a key
                                <ExternalLink size={11} aria-hidden="true" />
                            </a>
                        </div>
                        <Input
                            type="password"
                            autoComplete="off"
                            value={data.api_key}
                            onChange={(event) =>
                                setData('api_key', event.target.value)
                            }
                            placeholder={
                                selected.has_key
                                    ? 'A key is stored — leave blank to keep it'
                                    : `Paste your ${selected.label} key`
                            }
                            className="w-full border-border bg-background text-sm"
                        />
                        <p
                            className={cn(
                                'text-xs',
                                selected.has_key
                                    ? 'text-muted-foreground'
                                    : 'text-amber-700 dark:text-amber-400',
                            )}
                        >
                            {selected.has_key
                                ? 'Stored encrypted. It is never sent back to this page.'
                                : 'No key stored yet — insights fall back to a rule-based brief.'}
                        </p>
                        {errors.api_key ? (
                            <p className="text-xs text-destructive">
                                {errors.api_key}
                            </p>
                        ) : null}
                    </div>

                    <div className="flex flex-col gap-1.5">
                        <div className="flex items-center justify-between gap-3">
                            <Label className="text-sm font-medium text-foreground">
                                Model
                            </Label>
                            <Button
                                type="button"
                                variant="ghost"
                                size="compact"
                                disabled={refreshing}
                                onClick={refreshModels}
                            >
                                <RefreshCw
                                    size={12}
                                    aria-hidden="true"
                                    className={
                                        refreshing ? 'animate-spin' : undefined
                                    }
                                />
                                Refresh models
                            </Button>
                        </div>

                        {models.length > 0 ? (
                            <Select
                                value={data.model}
                                onValueChange={(value) =>
                                    setData('model', value)
                                }
                            >
                                <SelectTrigger className="w-full border-border bg-background text-sm">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent className="max-h-72">
                                    {models.map((model) => (
                                        <SelectItem key={model} value={model}>
                                            {model}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        ) : (
                            <Input
                                value={data.model}
                                onChange={(event) =>
                                    setData('model', event.target.value)
                                }
                                placeholder={selected.default_model}
                                className="w-full border-border bg-background font-mono text-sm"
                            />
                        )}

                        {refreshNote ? (
                            <p className="text-xs text-muted-foreground">
                                {refreshNote}
                            </p>
                        ) : null}
                        {errors.model ? (
                            <p className="text-xs text-destructive">
                                {errors.model}
                            </p>
                        ) : null}
                    </div>

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={processing}>
                            Save
                        </Button>

                        {recentlySuccessful ? (
                            <p className="inline-flex items-center gap-1.5 text-sm text-muted-foreground">
                                <Check size={14} aria-hidden="true" />
                                Saved
                            </p>
                        ) : null}
                    </div>
                </form>
            </div>
        </>
    );
}

AiSettings.layout = {
    breadcrumbs: [{ title: 'AI assistant', href: edit() }],
};
