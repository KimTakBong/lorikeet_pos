module.exports = {
  apps: [{
    name: 'wa-engine',
    script: 'server.js',
    cwd: './wa-engine',
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '1G',
    env: {
      NODE_ENV: 'production',
      PORT: 3000,
    },
  }],
};
