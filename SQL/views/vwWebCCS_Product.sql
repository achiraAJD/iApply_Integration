USE [LGS_UAT]
GO

/****** Object:  View [dbo].[vwWebCCS_Product]    Script Date: 3/06/2022 2:41:28 PM ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

ALTER VIEW [dbo].[vwWebCCS_Product]
AS
SELECT        dbo.CCS_Product.*
FROM            dbo.CCS_Product
GO


