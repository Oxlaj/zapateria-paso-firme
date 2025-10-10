# Diagrama de Red Macro — Volkswagen Guatemala

A continuación se presenta un diagrama macro de alto nivel para la red corporativa. Este diagrama sirve como base para detallar arquitectura física y lógica (espacios, cableado, servicios de proveedores y segmentación por departamentos).

```mermaid
flowchart TB

  %% Entradas/Proveedores
  subgraph Internet_Proveedores[Internet y Proveedores]
    ISP1[(ISP Primario)]
    ISP2[(ISP Secundario)]
    MPLS[(MPLS / SD-WAN Provider)]
    Cloud[(Nube Pública: IaaS/SaaS)]
  end

  %% Perímetro / DMZ
  subgraph Perimetro_DMZ[Perímetro y DMZ]
    FW_ACT{{Firewall Perimetral Activo}}
    FW_PAS{{Firewall Perimetral Pasivo}}
    WAF[WAF / Reverse Proxy]
    LB[Balanceador de Carga]
    VPN[Concentrador VPN SSL/IPsec]
    DDI[Servicios DDI (DNS/DHCP/IPAM)]
    PROXY[Proxy/Seguridad Web]
    IDS[IDS/IPS]
    DMZ_W[Web/App Servers DMZ]
    DMZ_M[Mail/Relay/SMTP]
  end

  %% Core / Datacenter
  subgraph DC[Datacenter Core]
    CORE1[[Core Switch 1]]
    CORE2[[Core Switch 2]]
    DIST1[[Distribución 1]]
    DIST2[[Distribución 2]]
  VMW[Cluster de Virtualización]
    STG[Cabina de Almacenamiento]
  BKP[Backup/DR]
    NAC[NAC/802.1X RADIUS]
    MON[Monitoreo (NMS/SIEM)]
    AD[Directorio/AAA]
    MAIL[Correo/Collab]
    ERP[ERP/DB Apps Internas]
  end

  %% Acceso / Departamentos (VLANs por área)
  subgraph Acceso[Capas de Acceso por Departamento]
    HR[Acceso RRHH]
    FIN[Acceso Finanzas]
    VTA[Acceso Ventas]
    MKT[Acceso Marketing]
    TI[Acceso TI]
    OPS[Acceso Operaciones/Logística]
    PD[Acceso Producción/Servicio]
    INV[Acceso Inventario]
    GEST[Acceso Gerencia]
    INVIT[Red de Invitados]
    IOT[Red IoT/Impresoras/CCTV]
  end

  %% Sucursales
  subgraph Sucursales[Agencias/Concesionarios / Talleres]
    SDWAN1[Edge SD-WAN Sucursal 1]
    SDWAN2[Edge SD-WAN Sucursal 2]
    SDWANn[Edge SD-WAN n]
    AP_B[APs WiFi Sucursales]
  end

  %% Conexiones Proveedores → Perímetro
  ISP1 -- Internet --> FW_ACT
  ISP2 -- Internet Backup --> FW_PAS
  MPLS -- Transporte/SD-WAN --> FW_ACT
  Cloud --- WAF

  %% Perímetro → DMZ y Core
  FW_ACT <--> FW_PAS
  FW_ACT --> WAF --> LB --> DMZ_W
  FW_ACT --> DMZ_M
  FW_ACT --> PROXY
  FW_ACT --> IDS
  FW_ACT --> VPN
  FW_ACT --> DDI
  FW_ACT --> CORE1
  FW_PAS --> CORE2

  %% Core redundante
  CORE1 <--> CORE2
  CORE1 --> DIST1
  CORE2 --> DIST2

  %% DC servicios
  DIST1 --> VMW
  DIST1 --> STG
  DIST1 --> BKP
  DIST1 --> NAC
  DIST1 --> MON
  DIST1 --> AD
  DIST1 --> ERP
  DIST1 --> MAIL
  DIST2 --> VMW
  DIST2 --> STG
  DIST2 --> BKP
  DIST2 --> NAC
  DIST2 --> MON
  DIST2 --> AD
  DIST2 --> ERP
  DIST2 --> MAIL

  %% Acceso por departamentos (aislados mediante VLAN/ACL/VRF)
  DIST1 --> HR
  DIST1 --> FIN
  DIST1 --> VTA
  DIST1 --> MKT
  DIST1 --> TI
  DIST1 --> OPS
  DIST1 --> PD
  DIST1 --> INV
  DIST1 --> GEST
  DIST1 --> INVIT
  DIST1 --> IOT

  DIST2 --> HR
  DIST2 --> FIN
  DIST2 --> VTA
  DIST2 --> MKT
  DIST2 --> TI
  DIST2 --> OPS
  DIST2 --> PD
  DIST2 --> INV
  DIST2 --> GEST
  DIST2 --> INVIT
  DIST2 --> IOT

  %% Sucursales por SD-WAN
  FW_ACT <--> SDWAN1
  FW_ACT <--> SDWAN2
  FW_ACT <--> SDWANn
  SDWAN1 --> AP_B
  SDWAN2 --> AP_B
  SDWANn --> AP_B

  %% Políticas de segmentación
  classDef aislado fill:#ffe8e8,stroke:#c33,stroke-width:1px
  class HR,FIN,VTA,MKT,TI,OPS,PD,INV,GEST aislado
```

Notas:
- La segmentación por áreas se implementará con VLANs/VRFs, ACLs y políticas NAC para aislamiento entre departamentos.
- La DMZ aloja aplicaciones públicas (web, correo relé) detrás de WAF/Load Balancer.
- Doble firewall (activo/pasivo) y doble core garantizan alta disponibilidad.
- SD‑WAN interconecta sucursales con control de rutas, QoS y seguridad.
- DDI centraliza DNS/DHCP/IPAM; AAA/AD provee identidad, 802.1X y RBAC.
