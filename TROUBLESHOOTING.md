# Guide de dépannage - Portfolio CMS

## Erreur "Forbidden" avec MAMP

Si vous obtenez une erreur "Forbidden" lors de l'accès au site, voici les solutions :

### 1. Vérifier les permissions

Assurez-vous que le serveur web (généralement `_www` ou `www-data`) peut lire les fichiers :

```bash
chmod -R 755 /chemin/vers/Hear-2026-portfolio
chmod -R 777 /chemin/vers/Hear-2026-portfolio/data
```

### 2. Vérifier la configuration MAMP

Dans MAMP, vérifiez que :
- Le port Apache est correct (généralement 8888)
- Le document root pointe vers le bon dossier
- Les modules Apache sont activés (mod_rewrite, mod_authz_core)

### 3. Activer AllowOverride dans MAMP

Si les `.htaccess` ne fonctionnent pas, vous devez activer `AllowOverride` dans la configuration Apache de MAMP :

1. Ouvrez MAMP
2. Allez dans **Preferences** > **Web Server** > **Apache**
3. Ouvrez le fichier `httpd.conf`
4. Trouvez la section `<Directory>` pour votre document root
5. Assurez-vous que `AllowOverride All` est présent (pas `AllowOverride None`)
6. Redémarrez MAMP

### 4. Tester sans .htaccess

Si le problème persiste, renommez temporairement le fichier `.htaccess` :

```bash
mv .htaccess .htaccess.bak
```

Puis testez l'accès à `http://localhost:8888/Hear-2026-portfolio/index.php` directement.

### 5. Utiliser le fichier de diagnostic

Accédez à `http://localhost:8888/Hear-2026-portfolio/diagnostic.php` pour voir un rapport détaillé des problèmes.

### 6. Vérifier les logs d'erreur

Consultez les logs d'erreur Apache dans MAMP :
- **MAMP** > **Logs** > **Apache Error Log**

### 7. Solution alternative : Configuration Apache directe

Si les `.htaccess` ne fonctionnent toujours pas, vous pouvez ajouter la configuration directement dans le `httpd.conf` de MAMP :

```apache
<Directory "/Applications/MAMP/htdocs/Hear-2026-portfolio">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

## Autres problèmes courants

### Le dossier data n'est pas créé automatiquement

Assurez-vous que PHP a les permissions d'écriture sur le répertoire parent.

### Les sessions ne fonctionnent pas

Vérifiez que le dossier de session PHP est accessible en écriture. Vous pouvez le voir dans `phpinfo()`.

### Les fichiers JSON ne sont pas sauvegardés

Vérifiez les permissions du dossier `data/` :
```bash
chmod 777 data
```

## Support

Si le problème persiste, consultez le fichier `diagnostic.php` pour un rapport détaillé.
