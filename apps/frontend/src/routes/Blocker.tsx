import { useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import { useParams, useLocation, Link } from "react-router-dom";
import styles from "./Blocker.module.scss";
import * as API from "aws-amplify/api";
import { Card } from "#src/components/Card.tsx";
import { DataRow } from "#src/components/DataRow.tsx";
import { PrismLight as SyntaxHighlighter } from "react-syntax-highlighter";
//import jsx from "react-syntax-highlighter/dist/esm/languages/prism/jsx";
import { a11yDark as prism } from "react-syntax-highlighter/dist/esm/styles/prism";
import { Skeleton, SkeletonDataRow } from "../components";

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

export const Blocker = () => {
  const { blockerId, auditId } = useParams();
  //const queryClient = useQueryClient();
  //const navigate = useNavigate();
  //const location = useLocation();

  const { data: blocker, isLoading } = useQuery({
    queryKey: ["blocker"],
    queryFn: async () => {
      if (!blockerId || !auditId) throw new Error("Blocker or Audit ID not found!");
      console.log(blockerId);
      console.log(auditId);

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
      console.log(response);
      return data.data.blockers[0] as theBlocker;
    },
  });

  return (
    <div className={styles["Blocker"]}>
      <Card variant="light" className={styles["Card"]}>
        {isLoading ??
          <>
            <Skeleton width={"100%"} height={30} />
            <SkeletonDataRow />
            <SkeletonDataRow />
            <SkeletonDataRow />
            <SkeletonDataRow />
            <SkeletonDataRow />
          </>
        }
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
        ) : (
          <>Error: Blocker {blockerId} not found.</>
        )}
      </Card >
    </div>
  );
};
