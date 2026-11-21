#!/bin/sh
# Checks on the kind cluster of the Helm chart test that matomo:install runs the update of a
# plugin. It installs a dummy plugin, raises the version of the plugin and installs again, the
# way a pod does when Matomo is updated, and looks at what the second install did.
#
# Needs the dummy plugin mounted at plugins/ExtraToolsDummy from /root/extratools-dummy on the
# kind node, and kubectl, helm, jq and docker.
set -eu

NODE="${KIND_NODE:-chart-testing-control-plane}"
NAMESPACE="${NAMESPACE:-matomo}"

in_pod() {
  kubectl -n "$NAMESPACE" exec deployment/matomo-dashboard -c matomo -- sh -c "cd /var/www/html && $1"
}

value() {
  helm -n "$NAMESPACE" get values matomo --all -o json | jq -r "$1"
}

# What the init container of the chart has in its environment for the install.
ENVIRONMENT="MATOMO_DB_HOST=$(value .db.hostname) MATOMO_DB_NAME=$(value .db.name) \
MATOMO_DB_PREFIX=$(value .db.prefix) MATOMO_DB_USERNAME=$(value .db.username) \
MATOMO_FIRST_USER_NAME=$(value .matomo.dashboard.firstuser.username) \
MATOMO_FIRST_USER_EMAIL=$(value .matomo.dashboard.firstuser.email) \
MATOMO_FIRST_USER_PASSWORD=$(value .matomo.dashboard.firstuser.password)"

install() {
  # A new pod has no config file, and that is when plugin updates used to be skipped.
  in_pod "rm -f config/config.ini.php \
    && $ENVIRONMENT ./console plugin:activate ExtraTools \
    && $ENVIRONMENT ./console matomo:install --install-file=/tmp/install-dummy.json --force --do-not-drop-db"
}

expect_status() {
  status=$(in_pod './console extratoolsdummy:status' | tail -n 1)
  echo "dummy plugin: $status"
  if [ "$status" != "$1" ]; then
    echo "::error::expected the dummy plugin to be at \"$1\", but it is at \"$status\""
    exit 1
  fi
}

# The install file of the chart, with the dummy plugin added.
kubectl -n "$NAMESPACE" get configmap matomo-configuration -o jsonpath='{.data.install\.json}' \
  | jq '.PluginsInstalled += ["ExtraToolsDummy"]' \
  | kubectl -n "$NAMESPACE" exec -i deployment/matomo-dashboard -c matomo -- sh -c 'cat > /tmp/install-dummy.json'

echo "::group::install with the dummy plugin at 1.0.0"
install
echo "::endgroup::"
expect_status 'version=1.0.0 column=no'

docker exec "$NODE" sed -i 's/"version": "1.0.0"/"version": "1.1.0"/' /root/extratools-dummy/plugin.json

echo "::group::install again with the dummy plugin at 1.1.0"
output=$(install)
echo "$output"
echo "::endgroup::"
if ! echo "$output" | grep -q 'The database was updated'; then
  echo "::error::matomo:install did not report that it updated the database"
  exit 1
fi
expect_status 'version=1.1.0 column=yes'
