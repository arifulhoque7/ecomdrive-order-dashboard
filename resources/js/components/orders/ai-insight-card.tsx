import { useHttp } from '@inertiajs/react';
import {
    AlertTriangle,
    Check,
    CheckCircle2,
    Copy,
    RefreshCw,
    Sparkles,
} from 'lucide-react';
import { useState } from 'react';
import insight from '@/actions/App/Http/Controllers/GenerateOrderInsightController';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useClipboard } from '@/hooks/use-clipboard';
import type { OrderDetail, OrderInsight } from '@/types/orders';

type InsightResponse = {
    insight: OrderInsight;
    generated_at: string | null;
};

/**
 * One model call answers all four questions the operator has about an order:
 * what happened, what to do next, what is missing, and what to tell the
 * customer. Falls back to a rule-based brief when no assistant is configured.
 */
export function AiInsightCard({ order }: { order: OrderDetail }) {
    const [result, setResult] = useState<OrderInsight | null>(order.ai_insight);
    const [generatedAt, setGeneratedAt] = useState(
        order.ai_insight_generated_at,
    );
    const [copied, copy] = useClipboard();

    const http = useHttp<{ refresh: boolean }, InsightResponse>({
        refresh: false,
    });

    const generate = (refresh: boolean) => {
        http.setData('refresh', refresh);

        http.post(insight.url(order.id), {
            onSuccess: (response) => {
                setResult(response.insight);
                setGeneratedAt(response.generated_at);
            },
        });
    };

    return (
        <section className="rounded-lg border border-border bg-card p-6 shadow-sm">
            <div className="flex items-start justify-between gap-3">
                <div className="flex items-center gap-2">
                    <span className="inline-flex size-7 items-center justify-center rounded-md bg-primary/10 text-primary">
                        <Sparkles size={14} aria-hidden="true" />
                    </span>
                    <h2 className="text-lg font-semibold">AI insight</h2>
                </div>

                {result ? (
                    <Button
                        type="button"
                        variant="ghost"
                        size="compact"
                        disabled={http.processing}
                        onClick={() => generate(true)}
                    >
                        <RefreshCw
                            size={13}
                            aria-hidden="true"
                            className={
                                http.processing ? 'animate-spin' : undefined
                            }
                        />
                        Regenerate
                    </Button>
                ) : null}
            </div>

            <div className="mt-4 mb-4 h-px w-full bg-border" />

            {http.processing && !result ? (
                <div className="space-y-3" role="status" aria-busy="true">
                    <Skeleton className="h-3.5 w-full" />
                    <Skeleton className="h-3.5 w-11/12" />
                    <Skeleton className="h-3.5 w-2/3" />
                    <Skeleton className="h-20 w-full rounded-md" />
                </div>
            ) : null}

            {!result && !http.processing ? (
                <div className="text-sm text-muted-foreground">
                    <p>
                        Summarise the activity, suggest the next actions, flag
                        missing details, and draft a customer reply.
                    </p>
                    <Button
                        type="button"
                        onClick={() => generate(false)}
                        className="mt-4"
                    >
                        <Sparkles
                            size={16}
                            strokeWidth={2}
                            aria-hidden="true"
                        />
                        Generate insight
                    </Button>
                </div>
            ) : null}

            {result ? (
                <div className="space-y-5">
                    <p className="text-sm leading-6 text-foreground">
                        {result.summary}
                    </p>

                    <div>
                        <h3 className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Next actions
                        </h3>
                        <ul className="mt-2 space-y-1.5">
                            {result.next_actions.map((action) => (
                                <li
                                    key={action}
                                    className="flex items-start gap-2 text-sm text-foreground"
                                >
                                    <CheckCircle2
                                        size={14}
                                        aria-hidden="true"
                                        className="mt-1 shrink-0 text-primary"
                                    />
                                    {action}
                                </li>
                            ))}
                        </ul>
                    </div>

                    {result.missing_info.length > 0 ? (
                        <div>
                            <h3 className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Missing information
                            </h3>
                            <ul className="mt-2 flex flex-wrap gap-2">
                                {result.missing_info.map((item) => (
                                    <li
                                        key={item}
                                        className="inline-flex items-center gap-1.5 rounded-md bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-500/15 dark:text-amber-300"
                                    >
                                        <AlertTriangle
                                            size={12}
                                            aria-hidden="true"
                                        />
                                        {item}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ) : null}

                    <div>
                        <div className="flex items-center justify-between">
                            <h3 className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Draft reply
                            </h3>
                            <Button
                                type="button"
                                variant="ghost"
                                size="compact"
                                onClick={() => copy(result.draft_reply)}
                            >
                                {copied === result.draft_reply ? (
                                    <Check size={12} aria-hidden="true" />
                                ) : (
                                    <Copy size={12} aria-hidden="true" />
                                )}
                                Copy
                            </Button>
                        </div>
                        <p className="mt-2 rounded-md border border-border bg-muted/30 p-3 text-sm leading-6">
                            {result.draft_reply}
                        </p>
                    </div>

                    <p className="text-xs text-muted-foreground">
                        Source: {result.source}
                        {generatedAt
                            ? ` · ${new Date(generatedAt).toLocaleString()}`
                            : null}
                    </p>
                </div>
            ) : null}
        </section>
    );
}
