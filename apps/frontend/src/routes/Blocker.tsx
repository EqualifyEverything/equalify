import { useQuery, useQueryClient } from "@tanstack/react-query";
import { useMemo, useState } from "react";
import { useParams, Link } from "react-router-dom";
import styles from "./Blocker.module.scss";
import * as API from "aws-amplify/api";
import { Card } from "#src/components/Card.tsx";
import { DataRow } from "#src/components/DataRow.tsx";
import { PrismLight as SyntaxHighlighter } from "react-syntax-highlighter";
import { a11yDark as prism } from "react-syntax-highlighter/dist/esm/styles/prism";
import { Skeleton, SkeletonDataRow } from "../components";
import { StyledButton } from "#src/components/StyledButton.tsx";
import { marked } from "marked";
import { GrPowerCycle } from "react-icons/gr";
import { MdOutlineFlag, MdSmartToy } from "react-icons/md";

const apiClient = API.generateClient();

interface BlockerTag {
  tag: {
    content: string;
  };
}

interface BlockerMessage {
  message: {
    category: string;
    content: string;
    message_tags: BlockerTag[];
  };
}

interface theBlocker {
  id: string;
  url: {
    url: string;
  };
  blocker_messages: BlockerMessage[];
  short_id: string;
  audit_id: string;
  ignored_blocker: null | Object;
  content: string;
  audits: {
    name: string;
    id: string;
  }
}

interface BlockerSummary {
  id?: string;
  summary?: string;
  cached?: boolean;
  flagged?: boolean;
  disabled?: boolean;
}

export const Blocker = () => {
  const { blockerId, auditId } = useParams();
  const queryClient = useQueryClient();
  const [isFlagging, setIsFlagging] = useState(false);
  const [isRefreshing, setIsRefreshing] = useState(false);

  const { data: blocker, isLoading, isPending, isFetching } = useQuery({
    queryKey: ["blocker-"+auditId+"-"+blockerId],
    queryFn: async () => {
      if (!blockerId || !auditId) throw new Error("Blocker or Audit ID not found!");
      //console.log(blockerId);
      //console.log(auditId);

      const response = await apiClient.graphql({
        query: `query($blocker_id: String!, $audit_id: uuid!)
        {
        blockers(where: {short_id: {_eq: $blocker_id}, _and: {audit_id: {_eq: $audit_id}}}) {
            id
            url {
                url
            }
            blocker_messages {
                message {
                    category
                    content
                    message_tags {
                        tag {
                            content
                        }
                    }
                }
            }
            short_id
            audit_id
            content
            ignored_blocker {
                blocker_id
            }
            audits {
                name
                id
            }
        }
      }
      `,
        variables: { blocker_id: blockerId, audit_id: auditId },
      });
      const data = response as any;
      //console.log(response);
      return data.data.blockers[0] as theBlocker;
    },
  });

  const summaryQueryKey = ["blocker-summary", blocker?.id];

  const { data: summary, isLoading: isSummaryLoading } = useQuery<BlockerSummary>({
    queryKey: summaryQueryKey,
    enabled: !!blocker?.id,
    queryFn: async () => {
      const response = await (
        await API.get({
          apiName: "public",
          path: "/getBlockerSummary",
          options: { queryParams: { blocker_id: blocker!.id } },
        }).response
      ).body.json();
      return response as unknown as BlockerSummary;
    },
  });

  const summaryHtml = useMemo(
    () => (summary?.summary ? (marked.parse(summary.summary) as string) : ""),
    [summary?.summary]
  );

  const handleRefresh = async () => {
    setIsRefreshing(true);
    try {
      const fresh = await (
        await API.get({
          apiName: "public",
          path: "/getBlockerSummary",
          options: { queryParams: { blocker_id: blocker!.id, refresh: "true" } },
        }).response
      ).body.json() as unknown as BlockerSummary;
      queryClient.setQueryData(summaryQueryKey, fresh);
    } finally {
      setIsRefreshing(false);
    }
  };

  const handleFlag = async () => {
    if (!summary?.id) return;
    setIsFlagging(true);
    try {
      await API.post({
        apiName: "public",
        path: "/flagBlockerSummary",
        options: { body: { summary_id: summary.id } },
      }).response;
      queryClient.setQueryData<BlockerSummary>(summaryQueryKey, { flagged: true });
    } finally {
      setIsFlagging(false);
    }
  };

  return (
    <div className={styles["Blocker"]}>
      <Card variant="light" className={styles["Card"]}>
        {((isLoading || isPending || isFetching) || !blocker ) ?
          <>
            <Skeleton width={"100%"} height={30} />
            <SkeletonDataRow />
            <SkeletonDataRow />
            <SkeletonDataRow />
            <SkeletonDataRow />
            <SkeletonDataRow />
          </>
        :null}
        {blocker ? (
          <>
            <h1>Blocker: {blocker.short_id}</h1>
            <DataRow the_key={"Appears on:"} the_value={<a href={blocker?.url.url}>{blocker?.url.url}</a>} />
            <DataRow the_key={"Audit:"} the_value={<Link to={"/shared/" + blocker?.audits.id}>{blocker?.audits.name}</Link>} />

            {blocker?.blocker_messages.map((messages, index) => {
              return <div key={index}>
                <DataRow the_key="Error:" the_value={messages.message.content} />

                <DataRow the_key="Category" the_value={
                  <div className="category tags">
                    <span className="tag">{messages.message.category}</span>
                  </div>
                } />

                <DataRow the_key="Tags" the_value=
                  {
                    <div className="tags">
                      {messages.message.message_tags.map((tag, index) => {
                        return <span key={index} className="tag">
                          {tag.tag.content}
                        </span>
                      })}
                    </div>
                  } />

              </div>
            })}

            <DataRow className={styles["dataRow_last"]} the_key="Code" the_value={
              <SyntaxHighlighter
                style={prism}
                language={"jsx"}
                className={styles["code"]}
                wrapLines={true}
                wrapLongLines={true}
              >
                {blocker.content}
              </SyntaxHighlighter>

            } />
          </>
        ):(null)}
        { (!blocker && !isLoading && !isPending) ? (
          <>Error: Blocker {blockerId} not found.</>
        ):(null)}
      </Card>

      {blocker && !summary?.disabled && (
        <Card variant="light" className={styles["SummaryCard"]}>
          <div className={styles["summaryHeader"]}>
            <h2><MdSmartToy aria-hidden="true" /> Blocker Summary</h2>
            <div className={styles["summaryActions"]}>
              <StyledButton
                label="Reload summary"
                icon={<GrPowerCycle />}
                onClick={handleRefresh}
                loading={isRefreshing}
                loadingText="Regenerating..."
                variant="light"
              />
              {summary?.summary && (
                <StyledButton
                  label="Flag a problem with this summary"
                  icon={<MdOutlineFlag />}
                  onClick={handleFlag}
                  loading={isFlagging}
                  loadingText="Flagging..."
                  variant="light"
                />
              )}
            </div>
          </div>

          {isSummaryLoading ? (
            <>
              <Skeleton width={"100%"} height={16} />
              <Skeleton width={"80%"} height={16} />
              <Skeleton width={"90%"} height={16} />
              <Skeleton width={"70%"} height={16} />
            </>
          ) : summaryHtml ? (
            <div
              className={styles["summaryContent"]}
              dangerouslySetInnerHTML={{ __html: summaryHtml }}
            />
          ) : (
            <p className={styles["summaryUnavailable"]}>
              No summary available. Click "Reload summary" to generate one.
            </p>
          )}
        </Card>
      )}
    </div>
  );
};
