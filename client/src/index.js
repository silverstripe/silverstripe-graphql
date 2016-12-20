import React from 'react';
import {render} from 'react-dom';
import GraphiQL from 'graphiql';
import fetch from 'isomorphic-fetch';
import 'graphiql/graphiql.css';

function graphQLFetcher(graphQLParams) {
  const baseURL = document.querySelector('base').href;
  return fetch(`${baseURL}/${GRAPHQL_ROUTE}/`, {
    method: 'post',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(graphQLParams),
    credentials: 'same-origin',
  }).then(response => response.json());
}

document.addEventListener('DOMContentLoaded', function () {
	render(
		<GraphiQL fetcher={graphQLFetcher} />,
		document.getElementById('graphiql')
	);
});
