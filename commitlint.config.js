export default {
  extends: ['@commitlint/config-conventional'],
  rules: {
    'scope-enum': [
      2,
      'always',
      ['api', 'web', 'ui', 'schemas', 'sdk', 'docker', 'deps', 'release', 'ci', 'docs'],
    ],
  },
};
