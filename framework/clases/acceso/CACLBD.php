<?php
class CACLBD extends CACLBase{
    
    private $_mysqli;
    private $_correcto;
    private $prefijo="!!";
    
    
    public function __construct($BD)
    {
        
       // conexion a la SQL
       $this->_mysqli = $BD->getEnlace();
       
       //Compruebo si se ha establecido o no la conexión.
       if ($this->_mysqli &&
           $this->_mysqli->connect_errno==0) {
           $this->_correcto=true;
         }
        else
        {
            $this->_correcto=false;
        }
       
    }
    
    public function valido()
    {
        return $this->_correcto;
    }
    
    public function existeRole($codRole)
    {
        if (!$this->valido())
            return false;
        
        $resultado = $this->_mysqli->query("SELECT nombre FROM acl_roles WHERE cod_role = $codRole");
        if (!$resultado)  //se ha producido un error
            return false;
        
        //if ($this->_mysqli->errno!=0) //error en la sentencia
        //    return false;
        
        if($resultado->num_rows == 0) {
            return false;
        } else {
            return true;
        }
        
    }
    
    public function getCodRole($nombre)
    {
        if (!$this->valido())
            return false;
            
        
        $nombre=mb_substr(mb_strtolower($nombre),0,30);
        $nombre=mysqli_real_escape_string($this->_mysqli, $nombre);
        
        $sentencia = "SELECT `cod_role` ".
                     "      FROM acl_roles ".
                     "      WHERE nombre='$nombre'";
       
        $resultado = $this->_mysqli->query($sentencia);
        if (!$resultado)  //se ha producido un error
            return false;
        
        if($resultado->num_rows == 0) 
            return false;
            
        
        $r =  $resultado->fetch_assoc();
        return $r["cod_role"];
        
    }

    public function anadirRole($nombre, $puedeAcceder, $puedeConfigurar,
        $permisoOtros = [])
    {
        if (!$this->valido())
            return false;
            
        
        $nombre=mb_substr(mb_strtolower($nombre),0,30);
        if ($this->getCodRole($nombre))
            return false;
        
        
        if (! is_bool($puedeAcceder)) {
            $acc = 0;
        } else {
            $acc = 1;
        }
        if (! is_bool($puedeConfigurar)) {
            $conf = 0;
        } else {
            $conf = 1;
        }
        if (! is_array($permisoOtros)) {
            $perm = [];
        } else {
            if (count($permisoOtros) > 10) {
                return false;
            }
            $perm = [];
            for ($cont=0;$cont<10;$cont++)
            {
                if (isset($permisoOtros[$cont]))
                    $perm[$cont]=boolval($permisoOtros[$cont]);
                    else
                        $perm[$cont]=0;
            }
        }
        
        $nombre=mysqli_real_escape_string($this->_mysqli, $nombre);
        $sentencia="INSERT INTO acl_roles (".
                    "   nombre,puede_acceder, puede_configurar, permiso1,permiso2, ".
                    "   permiso3,permiso4, permiso5, permiso6, permiso7,permiso8,  ".
                    "   permiso9, permiso10 ".
                    "       )VALUES(   ".
                    "   '$nombre',$acc,$conf,$perm[0],$perm[1],        ".
                    "   $perm[2],$perm[3],$perm[4],$perm[5],$perm[6], ".
                    "   $perm[7],$perm[8],$perm[9] ".
                    "  )";
        
        $resultado=$this->_mysqli->query($sentencia);
            
        return ($resultado!==false);
    }
    
        
    public function dameRoles()
    {
        if (!$this->valido())
            return false;
        
        $roles=[];
        
        $sentencia="select cod_role,nombre ".
                    "     from acl_roles".
                    "     order by cod_role";
        $resultado = $this->_mysqli->query($sentencia);
        while ($fila=$resultado->fetch_assoc())
        {
            $roles[$fila["cod_role"]]=$fila["nombre"];
        }
        
        
        return $roles;
            
    }
    
    
    public function getNombre($nick)
    {
        if (!$this->valido())
            return false;
        if (!$this->existeUsuario($nick))
            return false;
        
        $nick=mb_strtolower($nick);
        
        $sentencia = "SELECT nombre FROM acl_usuarios WHERE nick='$nick'";
        
        $resultado = $this->_mysqli->query($sentencia);
        if (!$resultado)  //se ha producido un error
            return false;
            
        if ($resultado->num_rows == 0)
            return false;
        $r =  $resultado->fetch_assoc();
        return $r['nombre'];
        
        
        return false;
    }
    
    public function getBorrado($nick)
    {
        if (! $this->valido())
            return false;
        if (! $this->existeUsuario($nick))
            return false;

        $nick = mb_strtolower($nick);

        $sentencia = "SELECT borrado FROM acl_usuarios WHERE nick='$nick'";

        $resultado = $this->_mysqli->query($sentencia);
        if (! $resultado) // se ha producido un error
            return false;

        if ($resultado->num_rows == 0)
            return false;
        $r = $resultado->fetch_assoc();
        return $r['borrado'];

        return false;
    }
    public function getUsuarioRole($nick)
    {
        if (! $this->valido())
            return false;
        if (! $this->existeUsuario($nick))
            return false;

        $nick = mb_strtolower($nick);

        $sentencia = "SELECT cod_role FROM acl_usuarios WHERE nick='$nick'";

        $resultado = $this->_mysqli->query($sentencia);
        if (! $resultado) // se ha producido un error
            return false;

        if ($resultado->num_rows == 0)
            return false;
        $r = $resultado->fetch_assoc();
        return $r['cod_role'];

        return false;
    }
    
    public function existeUsuario($nick)
    {
        if (!$this->valido())
            return false;
            
        
        $nick=mb_substr(mb_strtolower($nick),0,30);
        $nick=mysqli_real_escape_string($this->_mysqli,$nick);
        
        $sentencia = "SELECT nombre ".
                      "     FROM acl_usuarios ".
                      "     WHERE nick='$nick'";
        
        $resultado = $this->_mysqli->query($sentencia);
        if (!$resultado)  //se ha producido un error
            return false;
        
        return ($resultado->num_rows !== 0);
    }
    
    
    public function getPermisos($nick, &$puedeAcceder, &$puedeConfigurar)
    {
        if (!$this->valido())
            return false;
        if (!$this->existeUsuario($nick))
            return false;
        
        $nick=mb_substr(mb_strtolower($nick),0,30);
        $nick=mysqli_real_escape_string($this->_mysqli,$nick);
        
        $sentencia = "SELECT ar.puede_acceder, ar.puede_configurar ".
                     "      FROM acl_usuarios au ".
                     "          join acl_roles ar using (cod_role) ". 
                     "      WHERE nick='$nick'";
        $resultado = $this->_mysqli->query($sentencia);
        if (!$resultado)  //se ha producido un error
            return false;
            
        if ($resultado->num_rows == 0)
            return false;
        
        $fila=$resultado->fetch_assoc();
        $puedeAcceder=$fila["puede_acceder"];
        $puedeConfigurar=$fila["puede_configurar"];
        
        return true;
        
    }
    
    
    public function setNombre($nick, $nombre)
    {
        if (!$this->valido())
            return false;
        
        if (!$this->existeUsuario($nick))
            return false;
                
        $nick=mb_substr(mb_strtolower($nick),0,30);
        $nick=mysqli_real_escape_string($this->_mysqli,$nick);
        
        $nombre=mb_substr(mb_strtolower($nombre),0,30);
        $nombre=mysqli_real_escape_string($this->_mysqli,$nombre);
        
        $sentencia = "update acl_usuarios set ".
                    "      nombre='$nombre' ".
                    "      WHERE nick='$nick'";
        $resultado = $this->_mysqli->query($sentencia);
        if (!$resultado)  //se ha producido un error
            return false;
       
        return true;     
            
    }
    
    public function setNick($nick, $nuevoNick)
    {
        if (!$this->valido())
            return false;
            
            if (!$this->existeUsuario($nick))
                return false;
                
                $nick=mb_substr(mb_strtolower($nick),0,30);
                $nick=mysqli_real_escape_string($this->_mysqli,$nick);
                
                $nuevoNick=mysqli_real_escape_string($this->_mysqli,$nuevoNick);
                
                $sentencia = "update acl_usuarios set ".
                    "      nick='$nuevoNick' ".
                    "      WHERE nick='$nick'";
                $resultado = $this->_mysqli->query($sentencia);
                if (!$resultado)  //se ha producido un error
                    return false;
                    
                    return true;
                    
    }
    
    public function setContrasenia($nick, $contra)
    {
        if (!$this->valido())
            return false;
        if (! $this->existeUsuario($nick))
            return false;

        $nick = mb_substr(mb_strtolower($nick), 0, 30);
        $nick = mysqli_real_escape_string($this->_mysqli, $nick);

        $contra=mysqli_real_escape_string($this->_mysqli, $contra);
        $contra=$this->prefijo.$contra;
        
        $sentencia = "update acl_usuarios set " . "      contrasenia=md5('$contra') " . 
                     "      WHERE nick='$nick'";
        $resultado = $this->_mysqli->query($sentencia);
        if (! $resultado) // se ha producido un error
            return false;

        return true;
                    
    }
    
    public function setBorrado($nick,$borrado)
    {
        if (!$this->valido())
            return false;
        if (! $this->existeUsuario($nick))
            return false;

        $nick = mb_substr(mb_strtolower($nick), 0, 30);
        $nick = mysqli_real_escape_string($this->_mysqli, $nick);

        $borrado=($borrado===true?true:false);

        $sentencia = "update acl_usuarios set " . 
                     "      borrado=".($borrado?'1':'0') . 
                     "      WHERE nick='$nick'";
        $resultado = $this->_mysqli->query($sentencia);
        if (! $resultado) // se ha producido un error
            return false;

        return true;
    }
    /**
     * 
     * {@inheritDoc}
     * @see CACLBase::setUsuarioRole()
     */
    public function setUsuarioRole($nick,$role)
    {
        if (! $this->valido())
            return false;
        if (! $this->existeUsuario($nick))
            return false;

        $nick = mb_substr(mb_strtolower($nick), 0, 30);
        $nick = mysqli_real_escape_string($this->_mysqli, $nick);

        $role = (int)$role;
        
        if (!$this->existeRole($role))
            return false;

        $sentencia = "update acl_usuarios set " . 
                     "      cod_role=$role" .  
                     "      WHERE nick='$nick'";
        $resultado = $this->_mysqli->query($sentencia);
        if (! $resultado) // se ha producido un error
            return false;

        return true;
    }
    
    
    public function esValido($nick, $contrasena)
    {
        $prefijo="!!";
        if (!$this->valido())
            return false;
            
        if (!$this->existeUsuario($nick))
            return false;
            
        $nick=mb_substr(mb_strtolower($nick),0,30);
        $nick=mysqli_real_escape_string($this->_mysqli,$nick);
        
        $contrasena=mysqli_real_escape_string($this->_mysqli,$contrasena);
        $contrasena=$prefijo.$contrasena;
        
        $sentencia = "SELECT nombre ".
            "      FROM acl_usuarios ".
            "      WHERE nick='$nick' and contrasena=md5('$contrasena')".
            "           and not borrado";
        $resultado = $this->_mysqli->query($sentencia);
        if (!$resultado)  //se ha producido un error
            return false;
            
        if ($resultado->num_rows == 0)
            return false;
        return true;        
    }
    
    public function getPermisoOtros($nick, $numero)
    {
        if (!$this->valido())
            return false;
            
        if (!$this->existeUsuario($nick))
            return false;
        
        if ($numero<1 || $numero>10)
            return false;
        
        $nick=mb_substr(mb_strtolower($nick),0,30);
        $nick=mysqli_real_escape_string($this->_mysqli,$nick);
            
        $sentencia = "SELECT permiso$numero ".
            "      FROM acl_usuarios au ".
            "          join acl_roles ar using (cod_role) ".
            "      WHERE nick='$nick' ";
        $resultado = $this->_mysqli->query($sentencia);
        if (!$resultado)  //se ha producido un error
            return false;
            
        if ($resultado->num_rows == 0)
            return false;
        
        $fila=$resultado->fetch_assoc();
        return ($fila["permiso".$numero]); 
    }
    
    public function getPermisosOtros($nick)
    {
        if (!$this->valido())
            return false;
        if (!$this->existeUsuario($nick))
            return false;
            
        $nick=mb_substr(mb_strtolower($nick),0,30);
        $nick=mysqli_real_escape_string($this->_mysqli,$nick);
        
        $sentencia = "SELECT ar.* ".
            "      FROM acl_usuarios au ".
            "          join acl_roles ar using (cod_role) ".
            "      WHERE nick='$nick'";
        $resultado = $this->_mysqli->query($sentencia);
        if (!$resultado)  //se ha producido un error
            return false;
            
        if ($resultado->num_rows == 0)
            return false;
            
        $fila=$resultado->fetch_assoc();
        $per=[];
        for ($cont=1;$cont<=10;$cont++)
        {
            $per[$cont]=$fila["permiso".$cont];
        }
        
        return $per;
    }
    
    public function anadirUsuario($nombre, $nick, $contrasena, $codRole)
    {
        if (!$this->valido())
            return false;
            
        $nombre=mb_substr(mb_strtolower($nombre),0,30);
        if ($this->existeUsuario($nick))
            return false;
        
        $codRole=intval($codRole);
        if ($this->getCodRole($codRole))
            return false;
                
        $nombre=mb_substr(mb_strtolower($nombre),0,30);
        $nombre=mysqli_real_escape_string($this->_mysqli, $nombre);
        $nick=mysqli_real_escape_string($this->_mysqli, $nick);
        $contrasena=mysqli_real_escape_string($this->_mysqli, $contrasena);
        $contrasena=$this->prefijo.$contrasena;
        
        $sentencia="INSERT INTO acl_usuarios (".
            "   nombre,nick, contrasena, cod_role".
            "       )VALUES(   ".
            "   '$nombre','$nick',md5('$contrasena'), $codRole".
            "  )";
        
        $resultado=$this->_mysqli->query($sentencia);
        
        return ($resultado!==false);
    }
    
    public function dameUsuarios()
    {
        if (!$this->valido())
            return false;
            
        $usuarios=[];
        
        $sentencia="select cod_usuario,nombre ".
            "     from acl_usuarios".
            "     order by cod_usuario";
        $resultado = $this->_mysqli->query($sentencia);
        while ($fila=$resultado->fetch_assoc())
        {
            $usuarios[$fila["cod_usuario"]]=$fila["nombre"];
        }
        
            
        return $usuarios;
    }
    
}